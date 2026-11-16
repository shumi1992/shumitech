<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function socialFeedRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    exit;
}

function socialFeedLoadState(mysqli $conn): array
{
    $statement = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_social_interactions' LIMIT 1");
    $statement->execute();
    $statement->bind_result($json);
    $hasState = $statement->fetch();
    $statement->close();
    if (!$hasState) {
        return [];
    }

    $state = json_decode((string) $json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($state)) {
        throw new RuntimeException('Portfolio social interaction data is not valid JSON.');
    }
    return $state;
}

function socialFeedUpdateState(mysqli $conn, callable $update): array
{
    $conn->begin_transaction();
    try {
        $initialize = $conn->prepare(
            "INSERT INTO system_settings (setting_key, setting_value, setting_type)
             VALUES ('portfolio_social_interactions', '{}', 'json')
             ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key)"
        );
        $initialize->execute();
        $initialize->close();

        $statement = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_social_interactions' FOR UPDATE");
        $statement->execute();
        $statement->bind_result($json);
        $hasState = $statement->fetch();
        $statement->close();
        $state = $hasState ? json_decode((string) $json, true) : [];
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($state)) {
            throw new RuntimeException('Portfolio social interaction data is not valid JSON.');
        }

        $result = $update($state);
        $encodedState = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encodedState === false) {
            throw new RuntimeException('Could not encode portfolio social interaction data.');
        }
        $save = $conn->prepare("UPDATE system_settings SET setting_value = ?, setting_type = 'json' WHERE setting_key = 'portfolio_social_interactions'");
        $save->bind_param('s', $encodedState);
        $save->execute();
        $save->close();
        $conn->commit();
        return $result;
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}

function socialFeedPostState(array &$state, string $postId): array
{
    $postState = $state[$postId] ?? [];
    if (!is_array($postState)) {
        $postState = [];
    }
    foreach (['likes', 'shares'] as $key) {
        if (!isset($postState[$key]) || !is_array($postState[$key])) {
            $postState[$key] = [];
        }
    }
    if (!isset($postState['comments']) || !is_array($postState['comments'])) {
        $postState['comments'] = [];
    }
    foreach ($postState['comments'] as $index => &$comment) {
        if (!is_array($comment)) {
            $comment = [];
        }
        if (!isset($comment['id']) || !is_string($comment['id'])) {
            $comment['id'] = 'legacy-' . $index;
        }
        foreach (['likes', 'reactions', 'replies', 'shares'] as $key) {
            if (!isset($comment[$key]) || !is_array($comment[$key])) {
                $comment[$key] = [];
            }
        }
    }
    unset($comment);
    $postState['comment_count'] = isset($postState['comment_count']) && is_int($postState['comment_count'])
        ? max($postState['comment_count'], count($postState['comments']))
        : count($postState['comments']);
    $state[$postId] = $postState;
    return $postState;
}

function socialFeedVisibleCommentCount(array $postState): int
{
    $count = 0;
    foreach (($postState['comments'] ?? []) as $comment) {
        if (!is_array($comment) || !empty($comment['hidden'])) {
            continue;
        }
        $count++;
        foreach (($comment['replies'] ?? []) as $reply) {
            if (is_array($reply) && empty($reply['hidden'])) {
                $count++;
            }
        }
    }
    return $count;
}

function socialFeedViewerName(): string
{
    foreach (['social_feed_display_name', 'full_name', 'username', 'name'] as $key) {
        if (isset($_SESSION[$key]) && is_string($_SESSION[$key]) && trim($_SESSION[$key]) !== '') {
            preg_match('/^.{0,80}/us', trim($_SESSION[$key]), $matches);
            return $matches[0] ?? 'Visitor';
        }
    }
    return 'Visitor';
}

function socialFeedAdminRecordToken(string $postId, string $kind, string $commentId, string $recordId): string
{
    return hash_hmac('sha256', implode("\0", [$postId, $kind, $commentId, $recordId]), session_id());
}

$socialReactionChoices = ['❤️', '👍', '😂', '😮', '😢', '🎉'];
$postIds = ['portfolio-update', 'web-platform', 'web-application'];
$allPostIds = $postIds;

try {
    $settingsStatement = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_social_posts' LIMIT 1");
    $settingsStatement->execute();
    $settingsStatement->bind_result($configuredPostsJson);
    if ($settingsStatement->fetch()) {
        $configuredPosts = json_decode((string) $configuredPostsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($configuredPosts)) {
            throw new RuntimeException('Portfolio social posts setting is not valid JSON.');
        }
        $postIds = [];
        $allPostIds = [];
        foreach ($configuredPosts as $post) {
            if (is_array($post) && isset($post['id']) && is_string($post['id']) &&
                preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/', $post['id']) === 1) {
                $allPostIds[] = $post['id'];
                if (!isset($post['visible']) || $post['visible'] === true) {
                    $postIds[] = $post['id'];
                }
            }
        }
        $postIds = array_values(array_unique($postIds));
        $allPostIds = array_values(array_unique($allPostIds));
    }
    $settingsStatement->close();

    $action = $_GET['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'admin_load') {
        if (!isLoggedIn() || !hasRole('admin')) {
            socialFeedRespond(403, ['error' => 'Administrator access is required to view social feedback.']);
        }
        $state = socialFeedLoadState($conn);
        $posts = [];
        foreach ($allPostIds as $postId) {
            $postState = socialFeedPostState($state, $postId);
            $items = [];
            $appendItem = static function (
                string $kind,
                string $commentId,
                string $recordId,
                string $name,
                string $commentText,
                string $replyText,
                string $activityText,
                string $createdAt,
                bool $hidden,
                bool $editable
            ) use (&$items, $postId): void {
                $items[] = [
                    'kind' => $kind,
                    'record_token' => socialFeedAdminRecordToken($postId, $kind, $commentId, $recordId),
                    'comment_id' => $commentId,
                    'record_id' => in_array($kind, ['comment', 'reply'], true) ? $recordId : '',
                    'name' => $name,
                    'comment' => $commentText,
                    'reply' => $replyText,
                    'activity' => $activityText,
                    'created_at' => $createdAt,
                    'hidden' => $hidden,
                    'editable' => $editable,
                ];
            };
            foreach ($postState['likes'] as $recordId => $name) {
                if (is_string($name)) {
                    $appendItem('post_like', '', (string) $recordId, $name, '', '', 'Liked this post', '', !empty($postState['hidden_likes'][$recordId]), false);
                }
            }
            foreach ($postState['shares'] as $recordId => $name) {
                if (is_string($name)) {
                    $appendItem('post_share', '', (string) $recordId, $name, '', '', 'Shared this post; destination is not recorded', '', !empty($postState['hidden_shares'][$recordId]), false);
                }
            }
            foreach (array_reverse(array_values($postState['comments'])) as $comment) {
                if (!is_array($comment)) {
                    continue;
                }
                $commentId = isset($comment['id']) && is_string($comment['id']) ? $comment['id'] : '';
                $commentText = isset($comment['text']) && is_string($comment['text']) ? $comment['text'] : '';
                $commentName = isset($comment['name']) && is_string($comment['name']) ? $comment['name'] : 'Visitor';
                $appendItem(
                    'comment',
                    $commentId,
                    $commentId,
                    $commentName,
                    $commentText,
                    '',
                    '',
                    isset($comment['created_at']) && is_string($comment['created_at']) ? $comment['created_at'] : '',
                    !empty($comment['hidden']),
                    true
                );
                foreach (($comment['replies'] ?? []) as $reply) {
                    if (is_array($reply)) {
                        $replyId = isset($reply['id']) && is_string($reply['id']) ? $reply['id'] : '';
                        $appendItem(
                            'reply',
                            $commentId,
                            $replyId,
                            isset($reply['name']) && is_string($reply['name']) ? $reply['name'] : 'Visitor',
                            $commentText,
                            isset($reply['text']) && is_string($reply['text']) ? $reply['text'] : '',
                            '',
                            isset($reply['created_at']) && is_string($reply['created_at']) ? $reply['created_at'] : '',
                            !empty($reply['hidden']),
                            true
                        );
                    }
                }
                foreach (($comment['likes'] ?? []) as $recordId => $name) {
                    if (is_string($name)) {
                        $appendItem('comment_like', $commentId, (string) $recordId, $name, $commentText, '', 'Liked this comment', '', !empty($comment['hidden_likes'][$recordId]), false);
                    }
                }
                foreach (($comment['reactions'] ?? []) as $recordId => $reaction) {
                    if (is_array($reaction)) {
                        $appendItem(
                            'comment_reaction',
                            $commentId,
                            (string) $recordId,
                            isset($reaction['name']) && is_string($reaction['name']) ? $reaction['name'] : 'Visitor',
                            $commentText,
                            '',
                            'Reacted ' . (isset($reaction['emoji']) && is_string($reaction['emoji']) ? $reaction['emoji'] : ''),
                            '',
                            !empty($comment['hidden_reactions'][$recordId]),
                            false
                        );
                    }
                }
                foreach (($comment['shares'] ?? []) as $recordId => $name) {
                    if (is_string($name)) {
                        $appendItem('comment_share', $commentId, (string) $recordId, $name, $commentText, '', 'Shared this comment; destination is not recorded', '', !empty($comment['hidden_shares'][$recordId]), false);
                    }
                }
            }
            $posts[$postId] = [
                'items' => $items,
            ];
        }
        socialFeedRespond(200, ['posts' => $posts]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'admin_moderate') {
        if (!isLoggedIn() || !hasRole('admin')) {
            socialFeedRespond(403, ['error' => 'Administrator access is required to moderate social feedback.']);
        }
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['blog_dashboard2_csrf']) || !is_string($csrfToken) ||
            !hash_equals((string) $_SESSION['blog_dashboard2_csrf'], $csrfToken)) {
            socialFeedRespond(403, ['error' => 'Your admin session expired. Refresh the dashboard and try again.']);
        }
        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            socialFeedRespond(400, ['error' => 'The moderation request must be valid JSON.']);
        }
        $postId = $input['post_id'] ?? '';
        $kind = $input['kind'] ?? '';
        $commentId = $input['comment_id'] ?? '';
        $recordId = $input['record_id'] ?? '';
        $token = $input['record_token'] ?? '';
        $moderationAction = $input['moderation_action'] ?? '';
        if (!is_string($postId) || !in_array($postId, $allPostIds, true) ||
            !is_string($kind) || !in_array($kind, ['comment', 'reply', 'post_like', 'post_share', 'comment_like', 'comment_reaction', 'comment_share'], true) ||
            !is_string($commentId) || !is_string($recordId) || !is_string($token) ||
            strlen($commentId) > 40 || strlen($recordId) > 40 ||
            preg_match('/^[a-f0-9]{64}$/', $token) !== 1 ||
            (in_array($kind, ['comment', 'reply'], true) &&
                !hash_equals(socialFeedAdminRecordToken($postId, $kind, $commentId, $recordId), $token)) ||
            ($kind === 'comment' && ($commentId === '' || $commentId !== $recordId)) ||
            ($kind === 'reply' && ($commentId === '' || $recordId === '')) ||
            (in_array($kind, ['post_like', 'post_share'], true) && ($commentId !== '' || $recordId !== '')) ||
            (in_array($kind, ['comment_like', 'comment_reaction', 'comment_share'], true) &&
                ($commentId === '' || $recordId !== ''))) {
            socialFeedRespond(400, ['error' => 'Select a valid social feedback item.']);
        }
        if (!in_array($moderationAction, ['edit', 'hide', 'restore'], true)) {
            socialFeedRespond(400, ['error' => 'Select a valid moderation action.']);
        }
        $text = $input['text'] ?? '';
        if (!is_string($text)) {
            socialFeedRespond(400, ['error' => 'Enter valid feedback text.']);
        }
        $text = trim($text);
        $maxTextLength = $kind === 'reply' ? 1000 : 4000;
        if ($moderationAction === 'edit' &&
            ($text === '' || strlen($text) > $maxTextLength || preg_match('//u', $text) !== 1)) {
            socialFeedRespond(400, ['error' => 'Feedback text is required and must be within the supported length.']);
        }
        $result = socialFeedUpdateState($conn, static function (array &$state) use ($postId, $kind, $commentId, $recordId, $token, $moderationAction, $text): array {
            $postState = socialFeedPostState($state, $postId);
            if ($kind === 'post_like' || $kind === 'post_share') {
                $field = $kind === 'post_like' ? 'likes' : 'shares';
                $hiddenField = $kind === 'post_like' ? 'hidden_likes' : 'hidden_shares';
                $matchedRecordId = null;
                foreach ($postState[$field] as $candidateId => $_name) {
                    if (hash_equals(socialFeedAdminRecordToken($postId, $kind, '', (string) $candidateId), $token)) {
                        $matchedRecordId = (string) $candidateId;
                        break;
                    }
                }
                if ($matchedRecordId === null) {
                    throw new InvalidArgumentException('Social feedback item was not found.');
                }
                if ($moderationAction === 'edit') {
                    throw new InvalidArgumentException('This feedback item cannot be edited.');
                }
                $postState[$hiddenField] = is_array($postState[$hiddenField] ?? null) ? $postState[$hiddenField] : [];
                if ($moderationAction === 'hide') {
                    $postState[$hiddenField][$matchedRecordId] = true;
                } else {
                    unset($postState[$hiddenField][$matchedRecordId]);
                }
                $state[$postId] = $postState;
                return ['hidden' => $moderationAction === 'hide'];
            }
            foreach ($postState['comments'] as &$comment) {
                if (!is_array($comment) || ($comment['id'] ?? '') !== $commentId) {
                    continue;
                }
                if ($kind === 'comment') {
                    if ($moderationAction === 'edit') {
                        $comment['text'] = $text;
                    } else {
                        $comment['hidden'] = $moderationAction === 'hide';
                    }
                    $state[$postId] = $postState;
                    return ['text' => $comment['text'] ?? '', 'hidden' => !empty($comment['hidden'])];
                }
                if ($kind === 'reply') {
                    foreach ($comment['replies'] as &$reply) {
                        if (!is_array($reply) || ($reply['id'] ?? '') !== $recordId) {
                            continue;
                        }
                        if ($moderationAction === 'edit') {
                            $reply['text'] = $text;
                        } else {
                            $reply['hidden'] = $moderationAction === 'hide';
                        }
                        $state[$postId] = $postState;
                        return ['text' => $reply['text'] ?? '', 'hidden' => !empty($reply['hidden'])];
                    }
                    unset($reply);
                    throw new InvalidArgumentException('Social feedback item was not found.');
                }
                $interactionFields = [
                    'comment_like' => ['likes', 'hidden_likes'],
                    'comment_reaction' => ['reactions', 'hidden_reactions'],
                    'comment_share' => ['shares', 'hidden_shares'],
                ];
                [$field, $hiddenField] = $interactionFields[$kind];
                $matchedRecordId = null;
                foreach (($comment[$field] ?? []) as $candidateId => $_value) {
                    if (hash_equals(socialFeedAdminRecordToken($postId, $kind, $commentId, (string) $candidateId), $token)) {
                        $matchedRecordId = (string) $candidateId;
                        break;
                    }
                }
                if ($matchedRecordId === null) {
                    throw new InvalidArgumentException('Social feedback item was not found.');
                }
                if ($moderationAction === 'edit') {
                    throw new InvalidArgumentException('This feedback item cannot be edited.');
                }
                $comment[$hiddenField] = is_array($comment[$hiddenField] ?? null) ? $comment[$hiddenField] : [];
                if ($moderationAction === 'hide') {
                    $comment[$hiddenField][$matchedRecordId] = true;
                } else {
                    unset($comment[$hiddenField][$matchedRecordId]);
                }
                $state[$postId] = $postState;
                return ['hidden' => $moderationAction === 'hide'];
            }
            unset($comment);
            throw new InvalidArgumentException('Social feedback item was not found.');
        });
        socialFeedRespond(200, $result);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'load') {
        $state = socialFeedLoadState($conn);
        $viewerKey = hash('sha256', session_id());
        $posts = [];
        foreach ($postIds as $postId) {
            $postState = socialFeedPostState($state, $postId);
            $hiddenPostLikes = is_array($postState['hidden_likes'] ?? null) ? $postState['hidden_likes'] : [];
            $hiddenPostShares = is_array($postState['hidden_shares'] ?? null) ? $postState['hidden_shares'] : [];
            $visiblePostLikes = array_diff_key($postState['likes'], $hiddenPostLikes);
            $visiblePostShares = array_diff_key($postState['shares'], $hiddenPostShares);
            $visibleComments = array_values(array_filter(
                $postState['comments'],
                static fn($comment): bool => is_array($comment) &&
                    (empty($comment['hidden']) ||
                        (isset($comment['owner_key']) && hash_equals((string) $comment['owner_key'], $viewerKey)))
            ));
            foreach ($visibleComments as &$comment) {
                $hiddenCommentLikes = is_array($comment['hidden_likes'] ?? null) ? $comment['hidden_likes'] : [];
                $hiddenCommentShares = is_array($comment['hidden_shares'] ?? null) ? $comment['hidden_shares'] : [];
                $hiddenCommentReactions = is_array($comment['hidden_reactions'] ?? null) ? $comment['hidden_reactions'] : [];
                $visibleReplies = array_values(array_filter(
                    $comment['replies'],
                    static fn($reply): bool => is_array($reply) && empty($reply['hidden'])
                ));
                $comment['likes'] = array_diff_key($comment['likes'], $hiddenCommentLikes);
                $comment['shares'] = array_diff_key($comment['shares'], $hiddenCommentShares);
                $comment['reactions'] = array_diff_key($comment['reactions'], $hiddenCommentReactions);
                $comment['replies'] = $visibleReplies;
            }
            unset($comment);
            $comments = array_slice($visibleComments, -500);
            foreach ($comments as &$comment) {
                $comment['liked'] = isset($comment['likes'][$viewerKey]);
                $comment['my_reaction'] = $comment['reactions'][$viewerKey]['emoji'] ?? '';
                $comment['like_count'] = count($comment['likes']);
                $comment['share_count'] = count($comment['shares']);
                $comment['reactions'] = array_values($comment['reactions']);
                $comment['is_owner'] = isset($comment['owner_key']) && hash_equals((string) $comment['owner_key'], $viewerKey);
                $comment['is_hidden'] = !empty($comment['hidden']);
                $comment['replies'] = array_map(static function (array $reply) use ($viewerKey): array {
                    $reply['is_owner'] = isset($reply['owner_key']) && hash_equals((string) $reply['owner_key'], $viewerKey);
                    unset($reply['owner_key']);
                    return $reply;
                }, array_values(array_filter($comment['replies'], 'is_array')));
                unset($comment['owner_key'], $comment['hidden']);
            }
            unset($comment);
            $posts[$postId] = [
                'likes' => count($visiblePostLikes),
                'liked' => isset($visiblePostLikes[$viewerKey]),
                'likers' => array_values(array_map(
                    static fn($name): string => is_string($name) ? $name : 'Visitor',
                    array_slice($visiblePostLikes, 0, 100, true)
                )),
                'shares' => count($visiblePostShares),
                'shared' => isset($visiblePostShares[$viewerKey]),
                'sharers' => array_values(array_map(
                    static fn($name): string => is_string($name) ? $name : 'Visitor',
                    array_slice($visiblePostShares, 0, 100, true)
                )),
                'share_count' => count($visiblePostShares),
                'comments' => array_reverse($comments),
                'comment_count' => socialFeedVisibleCommentCount($postState),
            ];
        }
        socialFeedRespond(200, ['posts' => $posts]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        socialFeedRespond(405, ['error' => 'Unsupported request method.']);
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!isset($_SESSION['social_feed_csrf']) || !is_string($csrfToken) || !hash_equals($_SESSION['social_feed_csrf'], $csrfToken)) {
        socialFeedRespond(403, ['error' => 'Your page session expired. Refresh the page and try again.']);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) {
        socialFeedRespond(400, ['error' => 'The request body must be valid JSON.']);
    }

    $postId = $input['post_id'] ?? null;
    if (!is_string($postId) || !in_array($postId, $postIds, true)) {
        socialFeedRespond(400, ['error' => 'Select a valid post.']);
    }

    $viewerKey = hash('sha256', session_id());
    $viewerName = socialFeedViewerName();
    if (($input['action'] ?? '') === 'like') {
        $result = socialFeedUpdateState($conn, static function (array &$state) use ($postId, $viewerKey, $viewerName): array {
            $postState = socialFeedPostState($state, $postId);
            $postState['hidden_likes'] = is_array($postState['hidden_likes'] ?? null) ? $postState['hidden_likes'] : [];
            if (isset($postState['likes'][$viewerKey]) && empty($postState['hidden_likes'][$viewerKey])) {
                unset($postState['likes'][$viewerKey]);
                $liked = false;
            } else {
                $postState['likes'][$viewerKey] = $viewerName;
                unset($postState['hidden_likes'][$viewerKey]);
                $liked = true;
            }
            $state[$postId] = $postState;
            $visibleLikes = array_diff_key($postState['likes'], $postState['hidden_likes']);
            return [
                'likes' => count($visibleLikes),
                'liked' => $liked,
                'likers' => array_values(array_map(
                    static fn($name): string => is_string($name) ? $name : 'Visitor',
                    array_slice($visibleLikes, 0, 100, true)
                )),
            ];
        });
        socialFeedRespond(200, $result);
    }

    if (($input['action'] ?? '') === 'share') {
        $result = socialFeedUpdateState($conn, static function (array &$state) use ($postId, $viewerKey, $viewerName): array {
            $postState = socialFeedPostState($state, $postId);
            $postState['hidden_shares'] = is_array($postState['hidden_shares'] ?? null) ? $postState['hidden_shares'] : [];
            $postState['shares'][$viewerKey] = $viewerName;
            unset($postState['hidden_shares'][$viewerKey]);
            $state[$postId] = $postState;
            $visibleShares = array_diff_key($postState['shares'], $postState['hidden_shares']);
            return [
                'shares' => count($visibleShares),
                'shared' => true,
                'sharers' => array_values(array_filter($visibleShares, 'is_string')),
            ];
        });
        socialFeedRespond(200, $result);
    }

    if (in_array(($input['action'] ?? ''), [
        'comment_like', 'comment_react', 'comment_reply', 'comment_share',
        'comment_edit', 'comment_delete', 'comment_visibility', 'reply_edit', 'reply_delete'
    ], true)) {
        $commentId = $input['comment_id'] ?? null;
        if (!is_string($commentId) || $commentId === '' || strlen($commentId) > 40) {
            socialFeedRespond(400, ['error' => 'Select a valid comment.']);
        }
        $actionName = $input['action'];
        $reaction = $input['reaction'] ?? '';
        if (!is_string($reaction)) {
            socialFeedRespond(400, ['error' => 'Select a supported emoji reaction.']);
        }
        $replyInput = $input['text'] ?? '';
        if (!is_string($replyInput)) {
            socialFeedRespond(400, ['error' => 'Enter a valid reply.']);
        }
        $replyText = trim($replyInput);
        $actionText = $input['text'] ?? '';
        if (!is_string($actionText)) {
            socialFeedRespond(400, ['error' => 'Enter valid text.']);
        }
        $actionText = trim($actionText);
        $replyId = $input['reply_id'] ?? '';
        if (in_array($actionName, ['reply_edit', 'reply_delete'], true) &&
            (!is_string($replyId) || $replyId === '' || strlen($replyId) > 40)) {
            socialFeedRespond(400, ['error' => 'Select a valid reply.']);
        }
        if ($actionName === 'comment_react' && !in_array($reaction, $socialReactionChoices, true)) {
            socialFeedRespond(400, ['error' => 'Select a supported emoji reaction.']);
        }
        if (in_array($actionName, ['comment_reply', 'comment_edit', 'reply_edit'], true) &&
            ($actionText === '' || strlen($actionText) > 1000 || preg_match('//u', $actionText) !== 1)) {
            socialFeedRespond(400, ['error' => 'Enter a reply of up to 1,000 characters.']);
        }
        $hideComment = $input['hidden'] ?? null;
        if ($actionName === 'comment_visibility' && !is_bool($hideComment)) {
            socialFeedRespond(400, ['error' => 'Choose whether your comment should be hidden.']);
        }
        $result = socialFeedUpdateState($conn, static function (array &$state) use ($postId, $commentId, $replyId, $viewerKey, $viewerName, $actionName, $reaction, $actionText, $hideComment): array {
            $postState = socialFeedPostState($state, $postId);
            foreach ($postState['comments'] as $commentIndex => &$comment) {
                if (($comment['id'] ?? '') !== $commentId) {
                    continue;
                }
                if (in_array($actionName, ['comment_delete', 'comment_edit', 'comment_visibility'], true)) {
                    if (!isset($comment['owner_key']) || !hash_equals((string) $comment['owner_key'], $viewerKey)) {
                        throw new DomainException('You can only manage your own comments.');
                    }
                    if ($actionName === 'comment_delete') {
                        $removedCount = 1 + count($comment['replies']);
                        array_splice($postState['comments'], $commentIndex, 1);
                        $postState['comment_count'] = max(0, $postState['comment_count'] - $removedCount);
                        $result = ['deleted' => true, 'comment_count' => socialFeedVisibleCommentCount($postState)];
                    } else {
                        if ($actionName === 'comment_edit') {
                            $comment['text'] = $actionText;
                            $result = ['text' => $comment['text']];
                        } else {
                            $comment['hidden'] = $hideComment;
                            $result = [
                                'hidden' => $comment['hidden'],
                                'comment_count' => socialFeedVisibleCommentCount($postState),
                            ];
                        }
                    }
                } elseif ($actionName === 'comment_like') {
                    if (!empty($comment['hidden'])) {
                        throw new DomainException('This comment is hidden from viewers.');
                    }
                    $comment['hidden_likes'] = is_array($comment['hidden_likes'] ?? null) ? $comment['hidden_likes'] : [];
                    if (isset($comment['likes'][$viewerKey]) && empty($comment['hidden_likes'][$viewerKey])) {
                        unset($comment['likes'][$viewerKey]);
                    } else {
                        $comment['likes'][$viewerKey] = $viewerName;
                        unset($comment['hidden_likes'][$viewerKey]);
                    }
                    $visibleLikes = array_diff_key($comment['likes'], $comment['hidden_likes']);
                    $result = ['likes' => count($visibleLikes), 'liked' => isset($visibleLikes[$viewerKey])];
                } elseif ($actionName === 'comment_react') {
                    if (!empty($comment['hidden'])) {
                        throw new DomainException('This comment is hidden from viewers.');
                    }
                    $comment['hidden_reactions'] = is_array($comment['hidden_reactions'] ?? null) ? $comment['hidden_reactions'] : [];
                    if (($comment['reactions'][$viewerKey]['emoji'] ?? null) === $reaction && empty($comment['hidden_reactions'][$viewerKey])) {
                        unset($comment['reactions'][$viewerKey]);
                    } else {
                        $comment['reactions'][$viewerKey] = ['name' => $viewerName, 'emoji' => $reaction];
                        unset($comment['hidden_reactions'][$viewerKey]);
                    }
                    $visibleReactions = array_diff_key($comment['reactions'], $comment['hidden_reactions']);
                    $result = [
                        'reactions' => array_values($visibleReactions),
                        'my_reaction' => $visibleReactions[$viewerKey]['emoji'] ?? '',
                    ];
                } elseif ($actionName === 'comment_reply') {
                    if (!empty($comment['hidden'])) {
                        throw new DomainException('This comment is hidden from viewers.');
                    }
                    $reply = [
                        'id' => bin2hex(random_bytes(8)),
                        'name' => $viewerName,
                        'text' => $actionText,
                        'created_at' => date('Y-m-d H:i:s'),
                        'owner_key' => $viewerKey,
                    ];
                    $comment['replies'][] = $reply;
                    $postState['comment_count']++;
                    $reply['is_owner'] = true;
                    unset($reply['owner_key']);
                    $result = ['reply' => $reply, 'comment_count' => socialFeedVisibleCommentCount($postState)];
                } elseif ($actionName === 'comment_share') {
                    if (!empty($comment['hidden'])) {
                        throw new DomainException('This comment is hidden from viewers.');
                    }
                    $comment['hidden_shares'] = is_array($comment['hidden_shares'] ?? null) ? $comment['hidden_shares'] : [];
                    $comment['shares'][$viewerKey] = $viewerName;
                    unset($comment['hidden_shares'][$viewerKey]);
                    $visibleShares = array_diff_key($comment['shares'], $comment['hidden_shares']);
                    $result = ['shares' => count($visibleShares), 'sharers' => array_values($visibleShares)];
                }
                if ($actionName === 'reply_edit' || $actionName === 'reply_delete') {
                    $replyFound = false;
                    foreach ($comment['replies'] as $replyIndex => &$replyItem) {
                        if (($replyItem['id'] ?? '') !== $replyId) {
                            continue;
                        }
                        if (!isset($replyItem['owner_key']) || !hash_equals((string) $replyItem['owner_key'], $viewerKey)) {
                            throw new DomainException('You can only manage your own replies.');
                        }
                        $replyFound = true;
                        if ($actionName === 'reply_edit') {
                            $replyItem['text'] = $actionText;
                            $result = ['text' => $replyItem['text']];
                        } else {
                            array_splice($comment['replies'], $replyIndex, 1);
                            $postState['comment_count'] = max(0, $postState['comment_count'] - 1);
                            $result = ['deleted' => true, 'comment_count' => socialFeedVisibleCommentCount($postState)];
                        }
                        break;
                    }
                    unset($replyItem);
                    if (!$replyFound) {
                        throw new InvalidArgumentException('Reply was not found.');
                    }
                }
                $state[$postId] = $postState;
                return $result;
            }
            unset($comment);
            throw new InvalidArgumentException('Comment was not found.');
        });
        socialFeedRespond(200, $result);
    }

    if (($input['action'] ?? '') === 'comment') {
        $name = $input['name'] ?? null;
        $text = $input['text'] ?? null;
        if (!is_string($name) || !is_string($text)) {
            socialFeedRespond(400, ['error' => 'Enter your name and comment.']);
        }
        $name = trim($name);
        $text = trim($text);
        if ($name === '' || strlen($name) > 320 || $text === '' || strlen($text) > 4000) {
            socialFeedRespond(400, ['error' => 'Name is required (up to 80 characters) and comment is required (up to 1000 characters).']);
        }
        if (preg_match('//u', $name) !== 1 || preg_match('//u', $text) !== 1) {
            socialFeedRespond(400, ['error' => 'Use valid text for your name and comment.']);
        }

        $lastCommentAt = (int) ($_SESSION['social_feed_last_comment_at'] ?? 0);
        if (time() - $lastCommentAt < 10) {
            socialFeedRespond(429, ['error' => 'Please wait a few seconds before posting another comment.']);
        }

        $comment = [
            'id' => bin2hex(random_bytes(8)),
            'name' => $name,
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s'),
            'owner_key' => $viewerKey,
            'likes' => [],
            'reactions' => [],
            'replies' => [],
            'shares' => [],
        ];
        $result = socialFeedUpdateState($conn, static function (array &$state) use ($postId, $comment): array {
            $postState = socialFeedPostState($state, $postId);
            $postState['comments'][] = $comment;
            $postState['comment_count']++;
            $postState['comments'] = array_slice($postState['comments'], -500);
            $state[$postId] = $postState;
            $publicComment = $comment;
            $publicComment['is_owner'] = true;
            unset($publicComment['owner_key']);
            return ['comment' => $publicComment, 'comment_count' => socialFeedVisibleCommentCount($postState)];
        });
        $_SESSION['social_feed_display_name'] = $name;
        $_SESSION['social_feed_last_comment_at'] = time();
        socialFeedRespond(201, $result);
    }

    socialFeedRespond(400, ['error' => 'Unknown social feed action.']);
} catch (DomainException $exception) {
    socialFeedRespond(403, ['error' => $exception->getMessage()]);
} catch (InvalidArgumentException $exception) {
    socialFeedRespond(404, ['error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log('Social feed request failed: ' . $exception->getMessage());
    socialFeedRespond(500, ['error' => 'Social feed storage is unavailable. Check the server PHP error log and database permissions.']);
}
