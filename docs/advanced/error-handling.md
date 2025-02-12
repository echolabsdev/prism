<script setup>
import ExceptionSupport from '../components/ExceptionSupport.vue'
</script>

# Error handling

By default, Prism throws a `PrismException` for Prism errors, or a `PrismServerException` for Prism Server errors.

For production use cases, you may find yourself needing to catch Exceptions more granularly, for instance to provide more useful error messages to users or to implement failover or retry logic.

Prism has begun rolling out more specific exceptions as use cases arise.

## Provider agnostic exceptions

- `PrismStructuredDecodingException` where a provider has returned invalid JSON for a structured request.

## Exceptions based on provider feedback

Prism currently supports three exceptions based on provider feedback:

- `PrismRateLimitedException` where you have hit a rate limit or quota (see [Handling rate limits](/advanced/rate-limits.html) for more info).
- `PrismProviderOverloadedException` where the provider is unable to fulfil your request due to capacity issues.
- `PrismRequestTooLargeException` where your request is too large.

However, as providers all handle errors differently, support is being rolled out incrementally. If you'd like to make your first contribution, adding one or more of these exceptions for a provider would make a great first contribution. If you'd like to discuss, start an issue on Github, or just jump straight into a pull request.

<ExceptionSupport />