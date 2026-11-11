<?php

declare(strict_types=1);

namespace Traffman\Sdk\ContentBlock;

use Traffman\Sdk\Clock\ClockInterface;
use Traffman\Sdk\Config;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Credentials\CredentialsProviderInterface;
use Traffman\Sdk\Exception\MissingCredentialsException;
use Traffman\Sdk\Signature\Signer;
use Traffman\Sdk\Signature\UrlCanonicalizer;

final class ContentBlockHelper implements ContentBlockHelperInterface
{
    public const SCRIPT_PATH = 'clients/scripts/content/blocks.js';
    public const REDIRECT_PATH = 'content/redirects';

    public function __construct(
        private readonly Config $config,
        private readonly CredentialsProviderInterface $credentialsProvider,
        private readonly ClockInterface $clock,
    ) {
    }

    public function buildScriptUrl(
        string $requestUrl,
        string $blockClass,
        array $formData = [],
        ?string $language = null,
        array $extraQuery = [],
    ): string {
        $credentials = $this->requireCredentials();
        $timestamp = $this->clock->now()->getTimestamp();

        $query = array_merge($extraQuery, [
            'key' => $credentials->getApiKey(),
            'ts' => $timestamp,
            'requestUrl' => $requestUrl,
            'formData' => $formData,
            'blockClass' => $blockClass,
            'language' => $language,
        ]);

        $query = array_filter($query, static fn(mixed $v): bool => $v !== null && $v !== []);
        ksort($query);

        $url = $this->config->buildUrl(self::SCRIPT_PATH) . '?' . UrlCanonicalizer::buildQuery($query);

        return $url . '&sig=' . (new Signer($credentials->getApiSecret()))->signQuery($url, $timestamp);
    }

    public function signRedirect(string $blockId, string $contentId): string
    {
        return (new Signer($this->requireCredentials()->getApiSecret()))->signContentRedirect($blockId, $contentId);
    }

    public function buildRedirectUrl(string $blockId, string $contentId): string
    {
        return $this->config->buildUrl(sprintf(
            '%s/%s/%s/%s',
            self::REDIRECT_PATH,
            rawurlencode($contentId),
            rawurlencode($blockId),
            $this->signRedirect($blockId, $contentId),
        ));
    }

    private function requireCredentials(): Credentials
    {
        return $this->credentialsProvider->getCredentials()
            ?? throw new MissingCredentialsException('The API credentials are not configured.');
    }
}
