<?php

namespace AlexWells\GoodReflection\Util;

trait IsSingleton
{
	private static self $instance;

	public static function get(): static
	{
		return self::$instance ??= new static();
	}
}
