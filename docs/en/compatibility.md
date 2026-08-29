# Compatibility

## Runtime and dependencies

The SDK supports PHP 8.1 or newer, Composer 2, and the JSON extension. Its runtime dependencies are the PSR HTTP interfaces, Nyholm PSR-7, and Symfony HttpClient. The full Symfony Framework is not required.

Symfony HttpClient `6.4`, `7.4`, and `8.1` are allowed by Composer. Guzzle is optional: install it separately and use `GuzzleHttpClientFactory` only when your application chooses it.

## API baseline

The SDK implementation and its resource documentation are aligned with the current contract baseline stored in this repository. It exposes all 41 non-deprecated production operations; two deprecated upstream operations are intentionally excluded. The repository is not a statement that an unreleased SDK version, a future upstream API version, or any endpoint availability is guaranteed.

Use Stage for development and controlled verification. Keep Stage and Production credentials, URLs, token storage, and organization data isolated. The deferred integration procedure is documented in [Stage verification](https://github.com/DeveloperLancer/von-halsky-php-sdk/blob/main/tools/contract/STAGE.md).
