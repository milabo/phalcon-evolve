<?php

namespace Phalcon\Evolve\Model;
use Phalcon\Evolve\Security\Filter\SeparatedNumber;
use Phalcon\Evolve\System\DateTimeConvertible;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;
use Phalcon\Evolve\CustomGinq as Ginq;
use Phalcon\Db\Result\Pdo;
use Phalcon\Db\ResultInterface;

/**
 * Class CsvDataAdapter
 * @package Phalcon\Evolve\Model
 */
class CsvDataAdapter {
	use DateTimeConvertible;

	const NULL = '<null>';
	const FILTER_TAXONOMY = 'taxonomy';
	const FILTER_BOOL = 'bool';
	const FILTER_SEPARATED_NUMBER = 'separated_number';
	const FILTER_DATE = 'date';

	/**
	 * @param $class
	 * @param $csv_path
	 * @return Ginq
	 */
	public function load($class, $csv_path)
	{
		// 先頭行を property key として読み込んだデータをエンティティにアサイン
		if ($fp = fopen($csv_path, "r")) {
			$keys = fgetcsv($fp);
			$key_count = count($keys);
			fclose($fp);
			return Ginq::fromLazy(function() use ($class, $csv_path, &$keys, $key_count) {
				if ($fp = fopen($csv_path, 'r')) {
					fgets($fp); // 1行読み飛ばす
					while ($rec = fgetcsv($fp)) {
						if (!is_array($rec) or count($rec) == 0) continue;
						$has_value = false;
						foreach ($rec as $value) {
							if (!empty($value)) {
								$has_value = true;
								break;
							}
						}
						if (!$has_value) continue;
						$data = [];
						$values_count = count($rec);
						for ($i = 0; $i < $key_count && $i < $values_count; $i++) {
							$data[$keys[$i]] = $rec[$i] === '<null/>' ? null : $rec[$i];
						}
						/** @var ModelBase $entity */
						$entity = new $class();
						$entity->lightAssign($data);
						yield $entity;
					}
				}
			});
		} else {
			return Ginq::from([]);
		}
	}

	/**
	 * CSVとして出力する
	 * @param ModelBase $target_model
	 * @param ResultInterface|array|Ginq $source
	 * @param array $options [
	 *  'adapter' => 出力に使うStringWriteAdapterInterface,
	 *  'reference_views' => [ // 追加で出力する参照カラム定義
	 *      'カラム名' => レコード配列を引数に取り値を返す関数
	 *  ]
	 * ]
	 */
	public function writeAsCsv($target_model, $source, $options = [])
	{
		$options = Ax::x($options);
		$adapter = $options->getOrElse('adapter', null);
		$reference_views = $options->getOrElse('reference_views', []);
		$meta = $target_model->getModelsMetaData();
		$attributes = $meta->getAttributes($target_model);
		$reference_attributes = Ax::x($reference_views)->keys()->map(function($a) { return "(ref)$a"; })->toList();
		Ax::x($attributes)->pushRangeImmutable($reference_attributes)->toCsvLine_x()->write($adapter);
		if (is_array($source) and isset($source['sql'])) {
			$sql = $source['sql'];
			$placeholders = isset($source['placeholders']) ? $source['placeholders'] : null;
			$source = $target_model->getReadConnection()->query($sql, $placeholders);
		}
		// TODO Phalcon 2.x に移行したら Pdo を ResultInterface に変更する
		if ($source instanceof Pdo) {
			$rs = $source;
			$rs->setFetchMode(\Phalcon\Db::FETCH_NUM);
			$source = Ginq::fromLazy(function() use ($rs) {
				while ($rec = $rs->fetchArray()) {
					yield $rec;
				}
			});
		}
		foreach ($source as $rec) {
			$rec = Ax::x($rec)
				->map(function($v) { return is_null($v) ? self::NULL : $v; })
				->applyKeys($attributes);
			$ref_values = Ax::x($reference_views)
				->map(function($reference_view) use ($rec) {
					return $reference_view($rec);
				})->unwrap();
			Ax::x($rec)
				->pushRange($ref_values)
				->toCsvLine_x()
				->write($adapter);
		}
	}

	/**
	 * @param string $csv_path
	 * @param array $filters
	 * @return Ginq
	 * @throws \ErrorException
	 */
	public function loadAsAssoc($csv_path, $filters = [])
	{
		// 先頭行を key として読み込んだデータを連想配列にする
		if ($fp = fopen($csv_path, "r")) {
			$keys = fgetcsv($fp);
			$key_count = count($keys);
			fclose($fp);
			return Ginq::fromLazy(function() use ($csv_path, &$keys, $key_count, &$filters) {
				if ($fp = fopen($csv_path, 'r')) {
					fgets($fp); // 1行読み飛ばす
					while ($rec = fgetcsv($fp)) {
						if (!is_array($rec) or count($rec) == 0) continue;
						$has_value = false;
						foreach ($rec as $value) {
							if (!empty($value)) {
								$has_value = true;
								break;
							}
						}
						if (!$has_value) continue;
						$data = [];
						$values_count = count($rec);
						for ($i = 0; $i < $key_count && $i < $values_count; $i++) {
							$key = $keys[$i];
							$value = $rec[$i];
							if ($value === self::NULL) {
								$data[$key] = null;
							} else {
								if (isset($filters[$key])) $value = $this->filtering($value, $filters[$key]);
								$data[$key] = $value;
							}
						}
						yield $data;
					}
					fclose($fp);
				}
			});
		} else {
			throw new \ErrorException("loadAsAssoc: ファイルがオープンできません");
		}
	}

	private function filtering($value, $filter)
	{
		if (is_string($filter)) {
			switch ($filter) {
				case self::FILTER_TAXONOMY:
					return Sx::x($value)->replace('　', ' ')->split(' ', true);
				case self::FILTER_BOOL:
					return trim($value) === '1';
				case self::FILTER_SEPARATED_NUMBER:
					return (new SeparatedNumber())->filter($value);
				case self::FILTER_DATE:
					return $this->formatDateForSave($value);
				default:
					return call_user_func($filter, $value);
			}
		} else {
			return $filter($value);
		}
	}

}