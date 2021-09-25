<?php

namespace App\Http\Requests\Album;

use App\Http\Requests\BaseApiRequest;
use App\Http\Requests\Contracts\HasParentAlbumID;
use App\Http\Requests\Contracts\HasTitle;
use App\Http\Requests\Traits\HasParentAlbumIDTrait;
use App\Http\Requests\Traits\HasTitleTrait;
use App\Rules\ModelIDRule;
use App\Rules\TitleRule;

class AddAlbumRequest extends BaseApiRequest implements HasTitle, HasParentAlbumID
{
	use HasTitleTrait;
	use HasParentAlbumIDTrait;

	/**
	 * {@inheritDoc}
	 */
	public function authorize(): bool
	{
		return $this->authorizeAlbumWrite([$this->parentID]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function rules(): array
	{
		return [
			HasParentAlbumID::PARENT_ID_ATTRIBUTE => ['present', new ModelIDRule(true)],
			HasTitle::TITLE_ATTRIBUTE => ['required', new TitleRule()],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function processValidatedValues(array $values, array $files): void
	{
		$this->parentID = intval($values[HasParentAlbumID::PARENT_ID_ATTRIBUTE]) ?? null;
		$this->title = $values[HasTitle::TITLE_ATTRIBUTE];
	}
}
