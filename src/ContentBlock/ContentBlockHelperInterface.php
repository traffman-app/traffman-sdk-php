<?php

declare(strict_types=1);

namespace Traffman\Sdk\ContentBlock;

use Traffman\Sdk\Exception\MissingCredentialsException;

/**
 * Helpers of the content blocks rendered on the site pages.
 *
 * The flow: the page includes the script ({@see self::buildScriptUrl()}); the script posts the IDs of the blocks
 * to `requestUrl` of the site; the site gets the targeted content
 * ({@see \Traffman\Sdk\Resource\ContentResourceInterface::targeted()}) and responds with
 * `{"success": true, "blocks": {"<block ID>": {"id": "...", "content": "...", "sig": "..."}}}`, where `sig`
 * is {@see self::signRedirect()} of the block ID and the content item ID.
 */
interface ContentBlockHelperInterface
{
    /**
     * Signed URL of the script which fills the content blocks.
     *
     * @param string $requestUrl URL of the site endpoint the script posts the block IDs to.
     * @param string $blockClass CSS class of the block containers (`[a-z0-9_-]`).
     * @param array<string, string|int> $formData Extra fields posted to `$requestUrl` (up to 9).
     * @param string|null $language Language of the current page (ISO 639-1).
     * @param array<string, string|int> $extraQuery Extra query parameters, e.g. a version for browser caching.
     *
     * @throws MissingCredentialsException
     */
    public function buildScriptUrl(
        string $requestUrl,
        string $blockClass,
        array $formData = [],
        ?string $language = null,
        array $extraQuery = [],
    ): string;

    /**
     * Signature of the content redirect of a block (the `sig` of the block data).
     *
     * @throws MissingCredentialsException
     */
    public function signRedirect(string $blockId, string $contentId): string;

    /**
     * URL which redirects the visitor to the content (and counts the click).
     *
     * @throws MissingCredentialsException
     */
    public function buildRedirectUrl(string $blockId, string $contentId): string;
}
