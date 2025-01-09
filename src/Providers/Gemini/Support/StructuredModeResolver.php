<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Support;

use EchoLabs\Prism\Enums\StructuredMode as StructuredModeEnum;

class StructuredModeResolver
{
	public static function forModel(string $model): StructuredModeEnum
	{
		if (self::supportsStructuredMode($model)) {
			return StructuredModeEnum::Structured;
		}

		return StructuredModeEnum::Json;
	}

	protected static function supportsStructuredMode(string $model): bool
	{
		return in_array($model, [
			'gemini-2.0-flash-exp',
			'gemini-1.5-pro',
			'gemini-1.5-flash',
			'gemini-1.5-flash-8b'
		]);
	}

}
