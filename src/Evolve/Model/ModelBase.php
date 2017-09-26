<?php

namespace Phalcon\Evolve\Model;

use Phalcon\Evolve\System\DateTimeConvertible;
use Phalcon\Evolve\CustomGinq as Ginq;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\QueryInterface;

/**
 * Model 基底クラス
 * 主キーとしての id と作成日時・更新日時を提供する
 * @package App\Models
 */
class ModelBase extends Model {
	use DateTimeConvertible;

	/** @var integer */
	protected $id;
	/** @var integer */
	protected $created_ts;
	/** @var integer */
	protected $updated_ts;
	
	/** @var array */
	protected $volatiles = array();
	/** @type bool */
	protected $_destroy = false;

	// インスタンスキャッシュ
	/** @type array|self[] */
	protected static $instances = [];
	
	#region cache
	
	public function getCacheKey()
	{
		return Sx::x(get_class($this))->classNameToSnake('-');
	}

	/**
	 * @return \Phalcon\Cache\BackendInterface[]
	 */
	protected function getCacheInterfaces()
	{
		return [
			$this->getViewCache(),
			$this->getDataCache(),
			$this->getModelsCache(),
		];
	}

	/**
	 * id をキーとしてキャッシュされたデータをパージする
	 * アイテム単位でのキャッシュをクリアする目的で使う。
	 * @param integer $id
	 */
	public function purgeCachesById($id = null)
	{
		if (!$id) $id = $this->getId();
		$this->purgeCaches(":by-id:$id");
	}

	/**
	 * クラス単位でキャッシュをパージする
	 * 一覧や検索結果、カウント結果などのキャッシュをクリアする目的で使う。
	 */
	public function purgeCachesByClass()
	{
		$this->purgeCaches("");
	}

	public function purgeCaches($key_suffix = "")
	{
		$prefix = $this->getCacheKey() . ":$key_suffix";
		foreach ($this->getCacheInterfaces() as $cache) {
			foreach ($cache->queryKeys($prefix) as $key) $cache->delete($key);
		}
	}
	
	#endregion

	/**
	 * 論理削除フィールド名を取得する
	 * デフォルトで _destroy カラム, なければ null (物理削除)
	 * 下位クラスで継承することで変更する
	 * @return string|null
	 */
	protected function getDestroyField()
	{
		if ($this->getModelsMetaData()->hasAttribute($this, '_destroy')) {
			return '_destroy';
		}
		return null;
	}

	/**
	 * Method to set the value of field created_ts
	 *
	 * @param integer $created_ts
	 * @return static $this
	 */
	public function setCreatedTs($created_ts)
	{
		$this->created_ts = $created_ts;
		return $this;
	}

	/**
	 * Method to set the value of field created_ts
	 *
	 * @param \DateTime|string|integer $created
	 * @return static $this
	 */
	public function setCreated($created)
	{
		$this->created_ts = $this->anyToTimestamp($created);
		return $this;
	}

	/**
	 * Method to set the value of field updated_ts
	 *
	 * @param $updated_ts
	 * @return static $this
	 */
	public function setUpdatedTs($updated_ts)
	{
		$this->updated_ts = $updated_ts;
		return $this;
	}

	/**
	 * Method to set the value of field updated_ts
	 *
	 * @param \DateTime|string|integer $updated
	 * @return static $this
	 */
	public function setUpdated($updated)
	{
		$this->updated_ts = $this->anyToTimestamp($updated);
		return $this;
	}

	/**
	 * 一時的な値を格納する
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return static $this
	 */
	public function setVolatile($key, $value)
	{
		$this->volatiles[$key] = $value;
		return $this;
	}

	public function setVolatiles($volatiles)
	{
		$this->volatiles = $volatiles;
		return $this;
	}

	public function saveVolatilesIntoSession($key)
	{
		$this->getSession()->set($key, serialize($this->getVolatiles()));
		return $this;
	}
	
	public function loadVolatilesFromSession($key)
	{
		if ($serialized = $this->getSession()->get($key)) {
			$this->setVolatiles(unserialize($serialized));
		}
		return $this;
	}

	public function getId()
	{
		return intval($this->id);
	}

	/**
	 * View で使用する一時的な値を取得する
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getVolatile($key) {
		return isset($this->volatiles[$key]) ? $this->volatiles[$key] : null;
	}

	/**
	 * 一時データを削除する
	 * @param string $key
	 * @return static $this
	 */
	public function deleteVolatile($key)
	{
		unset($this->volatiles[$key]);
		return $this;
	}

	public function getVolatiles()
	{
		return $this->volatiles;
	}

	/**
	 * Returns the value of field created_ts
	 *
	 * @return integer
	 */
	public function getCreatedTs()
	{
		return intval($this->created_ts);
	}

	/**
	 * Returns the value of field created_ts
	 *
	 * @param string $format
	 * @param string $nullValue
	 * @return \DateTime|null|string
	 */
	public function getCreated($format = null, $nullValue = '-')
	{
		return $this->timestampToDatetime($this->created_ts, $format, $nullValue);
	}

	/**
	 * Returns the value of field updated_ts
	 *
	 * @return integer
	 */
	public function getUpdatedTs()
	{
		return $this->updated_ts;
	}

	/**
	 * Returns the value of field updated_ts
	 *
	 * @param string $format
	 * @param string $nullValue
	 * @return \DateTime|null|string
	 */
	public function getUpdated($format = null, $nullValue = '-')
	{
		return $this->timestampToDatetime($this->updated_ts, $format, $nullValue);
	}

	/**
	 * @return bool
	 */
	public function isSaved()
	{
		return is_numeric($this->id) and $this->id > 0;
	}

	public function beforeCreate()
	{
		$dt = $this->getClock()->now();
		if (!isset($this->created_ts)) $this->setCreated($dt);
		$this->setUpdated($dt);
	}

	public function beforeUpdate()
	{
		$dt = $this->getClock()->now();
		$this->setUpdated($dt);
	}

	/**
	 * @return \Phalcon\Mvc\Url
	 */
	public function getUrl()
	{
		return $this->getDI()->get('url');
	}

	/**
	 * @return \Phalcon\Http\Request
	 */
	public function getRequest()
	{
		return $this->getDI()->get('request');
	}

	/**
	 * @return \Phalcon\Session\AdapterInterface
	 */
	public function getSession()
	{
		return $this->getDI()->get('session');
	}

	/**
	 * @return \Phalcon\Logger\Adapter
	 */
	public function getLogger()
	{
		return $this->getDI()->get('logger');
	}

	/**
	 * @return \Redis
	 */
	public function getRedis()
	{
		return $this->getDI()->get('redis');
	}

	/**
	 * @return \Phalcon\Config
	 */
	public function getConfig()
	{
		return $this->getDI()->get('config');
	}

	/**
	 * @return \Phalcon\Cache\BackendInterface
	 */
	public function getModelsCache()
	{
		return $this->getDI()->get('modelsCache');
	}

	/**
	 * @return \Phalcon\Cache\BackendInterface
	 */
	public function getDataCache()
	{
		return $this->getDI()->get('cache');
	}

	/**
	 * @return \Phalcon\Cache\BackendInterface
	 */
	public function getViewCache()
	{
		return $this->getDI()->get('view')->getCache();
	}

	/**
	 * @return \Phalcon\Mvc\View\Engine\Volt
	 */
	public function getVolt()
	{
		return $this->getDI()->get('voltService', [$this->getDI()->get('view'), $this->getDI()]);
	}

	/**
	 * @return \Phalcon\Evolve\View\Translate
	 */
	protected function getTranslate()
	{
		return $this->getDI()->get('translate');
	}

	/**
	 * @return \Phalcon\Evolve\System\Clock
	 */
	public function getClock()
	{
		return $this->getDI()->get('clock');
	}

	/**
	 * @return \Phalcon\Filter
	 */
	public function getFilter()
	{
		return $this->getDI()->get('filter');
	}

	/**
	 * @return \Phalcon\Security
	 */
	public function getSecurity()
	{
		return $this->getDI()->get('security');
	}

	/**
	 * @return \Phalcon\Evolve\View\EventListenerPlugin\ExternalCushionPlugin
	 */
	public function getUrlConverter()
	{
		return $this->getDI()->get('urlConverter');
	}

	/**
	 * @return \Phalcon\Evolve\System\Debug
	 */
	protected function getDebug()
	{
		return $this->getDI()->get('debug');
	}

	public function destroy()
	{
		$this->_destroy = true;
	}

	public function save($data=null, $whiteList=null)
	{
		if ($this->_destroy) {
			if ($destroy_field = $this->getDestroyField()) {
				$this->$destroy_field = 1;
				return parent::save($data, $whiteList);
			} else {
				return $this->delete();
			}
		} else {
			return parent::save($data, $whiteList);
		}
	}

	/**
	 * @param array $data
	 * @param array $whiteList
	 * @return static $this
	 */
	public function lightAssign(array $data, $whiteList = null)
	{
		foreach ($data as $field => $value) {
			if (empty($whiteList) or Ax::x($whiteList)->contains($field)) {
				$this->_setValue($field, $value);
			}
		}
		return $this;
	}

	/**
	 * @param string $method
	 * @param string $line
	 * @param string $failed_message
	 * @param array $data
	 * @param array $whiteList
	 * @return static $this
	 * @throws \ErrorException
	 */
	public function trySave($method, $line, $failed_message = 'save failed', $data = null, $whiteList = null)
	{
		if (!empty($data)) $this->lightAssign($data, $whiteList);
		$this->save()
			or $this->handleSaveError($failed_message, $method, $line);
		return $this;
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 */
	public function _setValue($field, $value)
	{
		$setter = "set" . Sx::x($field)->toPascalCase();
		if (method_exists($this, $setter)) {
			$this->$setter($value);
		} else if (property_exists($this, $field)) {
			$this->$field = $value;
		}
	}

	public function _getValue($field, $params = [])
	{
		$getter = "get" . Sx::x($field)->toPascalCase();
		if (method_exists($this, $getter)) {
			switch (count($params)) {
				case 0: return $this->$getter();
				case 1: return $this->$getter($params[0]);
				case 2: return $this->$getter($params[0], $params[1]);
				case 3: return $this->$getter($params[0], $params[1], $params[2]);
				default:
					throw new \Exception("ModelBase::_getValue は4つ以上の引数を想定していません。");
			}
		} else if (property_exists($this, $field)) {
			return $this->$field;
		}
		return null;
	}

	public function dumpMessages()
	{
		foreach ($this->getMessages() as $message) echo $message . "\n";
	}

	/**
	 * @return static $this
	 */
	public function generateRandomId()
	{
		$this->id = rand(1, PHP_INT_MAX);
		return $this;
	}

	public function saveInstanceCache()
	{
		$class = Sx::x(get_class($this))->classNameToSnake();
		$id = $this->getId();
		$key = "$class:$id";
		self::$instances[$key] = $this;
		return $this;
	}

	/**
	 * 検索パラメタを追加・上書きする
	 * @param array|string|integer $parameters
	 * @param array $additional_parameters
	 * @return array
	 */
	protected static function extendParameters($parameters, $additional_parameters)
	{
		if (!is_array($parameters)) $parameters = array($parameters);
		foreach ($additional_parameters as $key => $value) $parameters[$key] = $value;
		return $parameters;
	}

	/**
	 * 検索パラメタを補完する
	 * 既にあるパラメタは上書きしない
	 * @param $parameters
	 * @param $filler_parameters
	 * @return array
	 */
	protected static function fillParameters($parameters, $filler_parameters)
	{
		if (!is_array($parameters)) $parameters = array($parameters);
		foreach ($filler_parameters as $key => $value) {
			if (!isset($parameters[$key])) $parameters[$key] = $value;
		}
		return $parameters;
	}

	/**
	 * toArray にてカラムを含めるか否かを判定する
	 * @param array $columns
	 * @param string $column_name
	 * @return bool
	 */
	protected static function including($columns, $column_name)
	{
		return (is_null($columns) || in_array($column_name, $columns)) ? $column_name : false;
	}

	/**
	 * JSON 変換用の配列を取得する
	 * @return array
	 * @throws \Exception
	 */
	public function pullArray()
	{
		$metaData = $this->getModelsMetaData();
		$attributes = $metaData->getAttributes($this);
		$columnMap = $metaData->getColumnMap($this);
		$data = [];
		foreach ($attributes as $attribute) {
			$field = $attribute;
			if (is_array($columnMap) and isset($columnMap[$attribute])) {
				$field = $columnMap[$attribute];
			}
			$value = $this->_getValue($field);
			if (is_object($value)) {
				$value = $this->$field;
			}
			$data[$field] = $value;
		}
		return $data;
	}

	/**
	 * @param string $message
	 * @param string $method
	 * @param int $line
	 * @param string $indent
	 * @throws \ErrorException
	 */
	public function handleSaveError($message, $method = __METHOD__, $line = __LINE__, $indent = "	") {
		$msg = "";
		foreach ($this->getMessages() as $m) {
			$msg .= $indent . $m->getMessage() . "\n";
		}
		$city_id = $this->getConfig()['application']['city_id'];
		$this->getLogger()->error("[$city_id] model save error: $message\n" . $msg . "\n at $method on line $line");
		throw new \ErrorException($message . "\n" . $msg);
	}

	/**
	 * プロパティの差分を取得
	 * @param array $new_data フィールド名 => 比較する値
	 * @param array $getter_params フィールド名 => getter 引数配列
	 * @return array フィールド名 => [
	 *  'old' => 前の値
	 *  'new' => 新しい値
	 *  'diff' => 差分
	 * ]
	 * @throws \Exception
	 */
	public function diff($new_data, $getter_params = [])
	{
		$result = [];
		foreach ($new_data as $field => $new_value) {
			$params = isset($getter_params[$field]) ? $getter_params[$field] : [];
			$value = $this->_getValue($field, $params);
			$old = $value;
			$new = $new_value;
			$diff = "";
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
                $new_value = self::formatDateForSave($new_value, 'Y-m-d H:i:s');
            }
			if (is_object($value)) {
				$value = "$value";
			}
			if (is_array($value)) {
				if (is_null($new_value)) $new_value = [];
				$removed = array_diff($value, $new_value);
				$inserted = array_diff($new_value, $value);
				$tmp = [];
				if (!empty($removed)) $tmp[] = "[-] " . implode(' ', $removed);
				if (!empty($inserted)) $tmp[] = "[+] " . implode(' ', $inserted);
				$diff = implode("\n", $tmp);
			} else if ($value != $new_value) {
				if (is_bool($value)) {
					$value = $value ? 'true' : 'false';
					$new_value = $new_value ? 'true' : 'false';
				}
				// TODO Git みたいな差分出力にする
				$diff .= " $value >> $new_value";
			}
			if (!empty($diff)) $result[$field] = [
				'old' => $old,
				'new' => $new,
				'diff' => $diff,
			];
		}
		return $result;
	}

    /**
     * 永続化されているデータと現在のオブジェクトのデータを比較し差分情報を生成する
     * @param string $name_field
     * @return array|null
     */
	public function makeDiffFromPreserved($name_field = null)
    {
        $id = $this->getId();
        $metaData = $this->getModelsMetaData();
        $attributes = $metaData->getAttributes($this);
        $columnMap = $metaData->getColumnMap($this);
        $data = [];
        foreach ($attributes as $attribute) {
            $field = $attribute;
            if (Ax::x(['id', 'created_ts', 'updated_ts'])->contains($field)) continue;
            if (is_array($columnMap) and isset($columnMap[$attribute])) {
                $field = $columnMap[$attribute];
            }
            $value = $this->_getValue($field);
            if (is_object($value)) {
                $value = $this->$field;
            }
            $data[$field] = $value;
        }
        if ($id > 0 && $preserved = static::findFirst($id)) {
            $diff = $preserved->diff($data);
            if (count($diff) > 0) {
                return [
                    'id' => $id,
                    'name' => $name_field ? $preserved->_getValue($name_field) : '',
                    'exists' => true,
                    'data' => Ax::x($diff)->toKeyValueList('field', 'value'),
                ];
            }
        } else {
            return [
                'id' => $id,
                'name' => $name_field ? $data[$name_field] : '',
                'exists' => false,
                'data' => Ax::x($data)->map(function($value) {
                    return [
                        'old' => null,
                        'new' => $value,
                        'diff' => $value,
                    ];
                })->toKeyValueList('field', 'value'),
            ];
        }
        return null;
    }

	/**
	 * @param Ginq $source
	 * @param string $name_field
	 * @param bool|false $apply
	 * @param array $getter_params フィールド名 => getter 引数配列
	 * @return array
	 * @throws \Exception
	 */
	protected static function _updateAll($source, $name_field, $apply = false, $getter_params = [])
	{
		$result = [];
		foreach ($source as $data) {
			$id = $data['id'];
			$data = Ax::x($data)->filter(function($v, $k) {
				list ($key) = explode(')', $k);
                return !Ax::x(['id', 'created_ts', 'updated_ts', '(ref'])->contains($key);
			}, true)->unwrap();
			if ($id > 0 && $self = static::findFirst($id)) {
				$diff = $self->diff($data, $getter_params);
				if (count($diff) > 0) {
					$result[] = [
						'id' => $id,
						'name' => $name_field ? $self->_getValue($name_field) : '',
						'exists' => true,
						'data' => Ax::x($diff)->toKeyValueList('field', 'value'),
					];
					if ($apply) {
						$self->lightAssign($data)->trySave(__METHOD__, __LINE__);
					}
				}
			} else {
				$result[] = [
					'id' => $id,
					'name' => $name_field ? $data[$name_field] : '',
					'exists' => false,
					'data' => Ax::x($data)->map(function($value) {
						return [
							'old' => null,
							'new' => $value,
							'diff' => $value,
						];
					})->toKeyValueList('field', 'value'),
				];
				if ($apply) {
					(new static())->lightAssign($data)->trySave(__METHOD__, __LINE__);
				}
			}
		}
		if ($apply) {
			(new static())->purgeCachesByClass();
		}
		return $result;
	}

	protected static function _repopulateAll($source)
	{
		$static = new static();
		$query = "DELETE FROM {$static->getClass()}";
		$static->getModelsManager()->executeQuery($query);
		foreach ($source as $data) {
			$data = Ax::x($data)->filter(function($v, $k) {
				list ($key) = explode(')', $k);
				switch ($key) {
					case 'created_ts':
					case 'updated_ts':
					case '(ref':
						return false;
					default:
						return true;
				}
			}, true)->unwrap();
			(new static())->lightAssign($data)->trySave(__METHOD__, __LINE__);
		}
	}

	/**
	 * @param ResultsetInterface|array|Ginq $result_set
	 * @return array
	 */
	public static function unwrapResultset($result_set)
	{
		if (is_array($result_set)) return $result_set;
		$list = [];
		foreach ($result_set as $result) {
			$list[] = $result;
		}
		return $list;
	}

	public static function lazy($result_set)
	{
		return function() use (&$result_set) {
			foreach ($result_set as $item) {
				yield $item;
			}
		};
	}

	/**
	 * @param ResultsetInterface|QueryInterface $result_set
	 * @return Ginq
	 */
	public static function toGinq($result_set)
	{
		return Ginq::fromLazy(self::lazy($result_set));
	}

	/**
	 * @param null $parameters
	 * @return Ginq
	 */
	public static function find($parameters = null)
	{
		return self::toGinq(parent::find($parameters));
	}

	public static function count($parameters = null)
	{
		return intval(parent::count($parameters));
	}

	protected function getClass()
	{
		return get_class($this);
	}

	/**
	 * @param integer $id
	 * @return ModelBase|static
	 */
	public static function loadInstance($id)
	{
		$class = Sx::x(get_called_class())->classNameToSnake();
		$key = "$class:$id";
		if (isset(self::$instances[$key])) return self::$instances[$key];
		return self::$instances[$key] = static::findFirst($id);
	}

	public static function clearInstanceCache()
	{
		self::$instances = [];
	}

	/**
	 * @param integer[]|Ax $ids
	 * @return self[]|Ginq
	 */
	public static function findByIds($ids)
	{
		$id_list = Ax::x($ids)->filter('is_numeric')->join(',');
		return self::find([
			'conditions' => "id IN ({$id_list})"
		]);
	}

}