<?php

/** @noinspection PhpUndefinedClassInspection */

namespace App\Models;

use App\Casts\DateTimeWithTimezoneCast;
use App\Models\Extensions\PhotoBooleans;
use App\Models\Extensions\PhotoCast;
use App\Models\Extensions\PhotoGetters;
use App\Models\Extensions\SizeVariants;
use App\Models\Extensions\UTCBasedTimes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * App\Photo.
 *
 * @property int          $id
 * @property string       $title
 * @property string|null  $description
 * @property string       $url
 * @property string       $tags
 * @property int          $public
 * @property int          $owner_id
 * @property string       $type
 * @property int|null     $width
 * @property int|null     $height
 * @property int          $filesize
 * @property string       $iso
 * @property string       $aperture
 * @property string       $make
 * @property string       $model
 * @property string       $lens
 * @property string       $shutter
 * @property string       $focal
 * @property float|null   $latitude
 * @property float|null   $longitude
 * @property float|null   $altitude
 * @property float|null   $imgDirection
 * @property string|null  $location
 * @property Carbon|null  $taken_at
 * @property string|null  $taken_at_orig_tz
 * @property int          $star
 * @property string       $thumbUrl
 * @property string       $livePhotoUrl
 * @property int|null     $album_id
 * @property string       $checksum
 * @property string       $license
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 * @property int|null     $medium_width
 * @property int|null     $medium_height
 * @property int|null     $medium2x_width
 * @property int|null     $medium2x_height
 * @property int|null     $small_width
 * @property int|null     $small_height
 * @property int|null     $small2x_width
 * @property int|null     $small2x_height
 * @property int          $thumb2x
 * @property string       $livePhotoContentID
 * @property string       $livePhotoChecksum
 * @property Album|null   $album
 * @property User         $owner
 * @property SizeVariants $size_variants
 *
 * @method static Builder|Photo ownedBy($id)
 * @method static Builder|Photo public ()
 * @method static Builder|Photo recent()
 * @method static Builder|Photo stars()
 * @method static Builder|Photo unsorted()
 * @method static Builder|Photo whereAlbumId($value)
 * @method static Builder|Photo whereAltitude($value)
 * @method static Builder|Photo whereAperture($value)
 * @method static Builder|Photo whereChecksum($value)
 * @method static Builder|Photo whereCreatedAt($value)
 * @method static Builder|Photo whereDescription($value)
 * @method static Builder|Photo whereFocal($value)
 * @method static Builder|Photo whereHeight($value)
 * @method static Builder|Photo whereId($value)
 * @method static Builder|Photo whereImgDirection($value)
 * @method static Builder|Photo whereLocation($value)
 * @method static Builder|Photo whereIso($value)
 * @method static Builder|Photo whereLatitude($value)
 * @method static Builder|Photo whereLens($value)
 * @method static Builder|Photo whereLicense($value)
 * @method static Builder|Photo whereLivePhotoChecksum($value)
 * @method static Builder|Photo whereLivePhotoContentID($value)
 * @method static Builder|Photo whereLivePhotoUrl($value)
 * @method static Builder|Photo whereLongitude($value)
 * @method static Builder|Photo whereMake($value)
 * @method static Builder|Photo whereMedium($value)
 * @method static Builder|Photo whereMedium2x($value)
 * @method static Builder|Photo whereModel($value)
 * @method static Builder|Photo whereOwnerId($value)
 * @method static Builder|Photo wherePublic($value)
 * @method static Builder|Photo whereShutter($value)
 * @method static Builder|Photo whereSize($value)
 * @method static Builder|Photo whereSmall($value)
 * @method static Builder|Photo whereSmall2x($value)
 * @method static Builder|Photo whereStar($value)
 * @method static Builder|Photo whereTags($value)
 * @method static Builder|Photo whereTakenAt($value)
 * @method static Builder|Photo whereThumb2x($value)
 * @method static Builder|Photo whereThumbUrl($value)
 * @method static Builder|Photo whereTitle($value)
 * @method static Builder|Photo whereType($value)
 * @method static Builder|Photo whereUpdatedAt($value)
 * @method static Builder|Photo whereUrl($value)
 * @method static Builder|Photo whereWidth($value)
 */
class Photo extends Model
{
	use PhotoBooleans;
	use PhotoCast;
	use PhotoGetters;
	use UTCBasedTimes;

	protected $casts = [
		'public' => 'int',
		'star' => 'int',
		'downloadable' => 'int',
		'share_button_visible' => 'int',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'taken_at' => DateTimeWithTimezoneCast::class,
	];

	/**
	 * @var string[] The list of attributes which exist as columns of the DB
	 *               relation but shall not be serialized to JSON
	 */
	protected $hidden = [
		'thumbUrl',  // serialized as part of size_variants
		'small_width',  // serialized as part of size_variants
		'small_height',  // serialized as part of size_variants
		'small2x_width',  // serialized as part of size_variants
		'small2x_height',  // serialized as part of size_variants
		'medium_width',  // serialized as part of size_variants
		'medium_height',  // serialized as part of size_variants
		'medium2x_width',  // serialized as part of size_variants
		'medium2x_height',  // serialized as part of size_variants
	];

	/**
	 * @var string[] The list of "virtual" attributes which do not exist as
	 *               columns of the DB relation but shall be appended to JSON
	 *               from accessors
	 */
	protected $appends = [
		'size_variants', // see getSizeVariantsAttribute()
	];

	/**
	 * @var SizeVariants|null caches the size variants associated to this class, once they have been created by {@link getSizeVariantsAttribute()}
	 */
	protected ?SizeVariants $sizeVariants = null;

	/**
	 * Return the relationship between a Photo and its Album.
	 *
	 * @return BelongsTo
	 */
	public function album(): BelongsTo
	{
		return $this->belongsTo('App\Models\Album', 'album_id', 'id');
	}

	/**
	 * Return the relationship between a Photo and its Owner.
	 *
	 * @return BelongsTo
	 */
	public function owner(): BelongsTo
	{
		return $this->belongsTo('App\Models\User', 'owner_id', 'id');
	}

	/**
	 * Before calling the delete() method which will remove the entry from the database, we need to remove the files.
	 *
	 * @param bool $keep_original
	 *
	 * @return bool True on success, false otherwise
	 */
	public function predelete(bool $keep_original = false): bool
	{
		if ($this->isDuplicate($this->checksum, $this->id)) {
			Logs::notice(__METHOD__, __LINE__, $this->id . ' is a duplicate!');
			// it is a duplicate, we do not delete!
			return true;
		}

		$error = false;
		$path_prefix = $this->type == 'raw' ? 'raw/' : 'big/';

		// Delete original file
		if ($keep_original === false) {
			// quick check...
			if (!Storage::exists($path_prefix . $this->url)) {
				Logs::error(__METHOD__, __LINE__, 'Could not find file in ' . Storage::path($path_prefix . $this->url));
				$error = true;
			} elseif (!Storage::delete($path_prefix . $this->url)) {
				Logs::error(__METHOD__, __LINE__, 'Could not delete file in ' . Storage::path($path_prefix . $this->url));
				$error = true;
			}
		}

		// Delete Live Photo Video file
		// TODO: USE STORAGE FOR DELETE
		// check first if livePhotoUrl is available
		if ($this->livePhotoUrl !== null) {
			if (!Storage::exists('big/' . $this->livePhotoUrl)) {
				Logs::error(__METHOD__, __LINE__, 'Could not find file in ' . Storage::path('big/' . $this->livePhotoUrl));
				$error = true;
			} elseif (!Storage::delete('big/' . $this->livePhotoUrl)) {
				Logs::error(__METHOD__, __LINE__, 'Could not delete file in ' . Storage::path('big/' . $this->livePhotoUrl));
				$error = true;
			}
		}

		$sizeVariants = $this->size_variants;

		// Delete medium
		// TODO: USE STORAGE FOR DELETE
		if (
			$sizeVariants->getThumb() &&
			Storage::exists($sizeVariants->getThumb()->getUrl()) &&
			!unlink(Storage::path($sizeVariants->getThumb()->getUrl()))
		) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete photo in uploads/thumb/');
			$error = true;
		}

		// TODO: USE STORAGE FOR DELETE
		if (
			$sizeVariants->getThumb2x() &&
			Storage::exists($sizeVariants->getThumb2x()->getUrl()) &&
			!unlink(Storage::path($sizeVariants->getThumb2x()->getUrl()))
		) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete high-res photo in uploads/thumb/');
			$error = true;
		}

		// TODO: USE STORAGE FOR DELETE
		if (
			$sizeVariants->getSmall() &&
			Storage::exists($sizeVariants->getSmall()->getUrl()) &&
			!unlink(Storage::path($sizeVariants->getSmall()->getUrl()))
		) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete photo in uploads/small/');
			$error = true;
		}

		// TODO: USE STORAGE FOR DELETE
		if (
			$sizeVariants->getSmall2x() &&
			Storage::exists($sizeVariants->getSmall2x()->getUrl()) &&
			!unlink(Storage::path($sizeVariants->getSmall2x()->getUrl()))
		) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete high-res photo in uploads/small/');
			$error = true;
		}

		// TODO: USE STORAGE FOR DELETE
		if (
			$sizeVariants->getMedium() &&
			Storage::exists($sizeVariants->getMedium()->getUrl()) &&
			!unlink(Storage::path($sizeVariants->getMedium()->getUrl()))
		) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete photo in uploads/medium/');
			$error = true;
		}

		// TODO: USE STORAGE FOR DELETE
		if (
			$sizeVariants->getMedium2x() &&
			Storage::exists($sizeVariants->getMedium2x()->getUrl()) &&
			!unlink(Storage::path($sizeVariants->getMedium2x()->getUrl()))
		) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete high-res photo in uploads/medium/');
			$error = true;
		}

		return !$error;
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public static function set_order(Builder $query)
	{
		$sortingCol = Configs::get_value('sorting_Photos_col');
		if ($sortingCol !== 'title' && $sortingCol !== 'description') {
			$query = $query->orderBy($sortingCol, Configs::get_value('sorting_Photos_order'));
		}

		return $query->orderBy('photos.id', 'ASC');
	}

	/**
	 * Define scopes which we can directly use e.g. Photo::stars()->all().
	 */

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeStars($query)
	{
		return $query->where('star', '=', 1);
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopePublic($query)
	{
		return $query->where('public', '=', 1);
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeRecent($query)
	{
		return $query->where('created_at', '>=', Carbon::now()->subDays(intval(Configs::get_value('recent_age', '1')))->toDateTimeString());
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeUnsorted($query)
	{
		return $query->where('album_id', '=', null);
	}

	/**
	 * @param $query
	 * @param $id
	 *
	 * @return mixed
	 */
	public function scopeOwnedBy(Builder $query, $id)
	{
		return $id == 0 ? $query : $query->where('owner_id', '=', $id);
	}

	public function withTags($tags)
	{
		$sql = $this;
		foreach ($tags as $tag) {
			$sql = $sql->where('tags', 'like', '%' . $tag . '%');
		}

		return ($sql->count() == 0) ? false : $sql->first();
	}

	protected function getSizeVariantsAttribute(): SizeVariants
	{
		if ($this->sizeVariants === null) {
			$this->sizeVariants = new SizeVariants($this);
		}

		return $this->sizeVariants;
	}
}
