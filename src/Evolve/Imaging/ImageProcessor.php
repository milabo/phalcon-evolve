<?php

namespace Phalcon\Evolve\Imaging;

class ImageProcessor {

	const IMAGE_RESIZE_BY_FIT = 'fit';
	const IMAGE_RESIZE_BY_FIT_AND_CROP = 'fitAndCrop';

	/**
	 * @param string $source_path
	 * @param string $target_path
	 * @param \stdClass $max_size includes width and height
	 * @param string $way ImageProcessor::IMAGE_RESIZE_BY_*
	 */
	public static function resizeAndSaveImageFile($source_path, $target_path, $max_size, $way = 'fit')
	{
		$exif = @exif_read_data($source_path);
		$imagedata = new \Dm_Image_File($source_path);
		if (is_array($exif) && array_key_exists('Orientation', $exif)) {
			// iPhone やコンデジで撮影した写真が回転してしまうため、Exif から回転情報を抽出
			switch ($exif['Orientation'])
			{
				case 1: // nothing
					break;

				case 2: // horizontal flip
					$imagedata->flip(\Dm_Image::FLIP_HORIZONTAL);
					break;

				case 3: // 180 rotate left
					$imagedata->rotate(180);
					break;

				case 4: // vertical flip
					$imagedata->flip(\Dm_Image::FLIP_VERTICAL);
					break;

				case 5: // vertical flip + 90 rotate right
					$imagedata->flip(\Dm_Image::FLIP_VERTICAL);
					$imagedata->rotate(-90);
					break;

				case 6: // 90 rotate right
					$imagedata->rotate(-90);
					break;

				case 7: // horizontal flip + 90 rotate right
					$imagedata->flip(\Dm_Image::FLIP_HORIZONTAL);
					$imagedata->rotate(-90);
					break;

				case 8:    // 90 rotate left
					$imagedata->rotate(90);
					break;
			}
		}
		switch ($way) {
			case self::IMAGE_RESIZE_BY_FIT:
				if ($imagedata->getWidth() > $max_size->width
					|| $imagedata->getHeight() > $max_size->height) {
					$imagedata->applyFilter(new \Dm_Image_Filter_Fit($max_size->width, $max_size->height));
				}
				break;
			case self::IMAGE_RESIZE_BY_FIT_AND_CROP:
				$imagedata->applyFilters(array(
					new \Dm_Image_Filter_Fit($max_size->width, $max_size->height, true),
					new \Dm_Image_Filter_Crop($max_size->width, $max_size->height),
				));
				break;
		}
		$imagedata->saveTo($target_path);
		$imagedata->destroy();
	}

}