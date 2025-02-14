<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

namespace App\Legacy\V1\Requests\Settings;

final class SetNewPhotosNotificationSettingRequest extends AbstractSettingRequest
{
	public const ATTRIBUTE = 'new_photos_notification';

	public function rules(): array
	{
		return [self::ATTRIBUTE => 'required|boolean'];
	}

	protected function processValidatedValues(array $values, array $files): void
	{
		$this->name = self::ATTRIBUTE;
		$this->value = self::toBoolean($values[self::ATTRIBUTE]);
	}
}
