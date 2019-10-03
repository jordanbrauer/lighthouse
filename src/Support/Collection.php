<?php

namespace Nuwave\Lighthouse\Support;

use Illuminate\Support\Collection as LaravelCollection;
use JsonSerializable;
use Traversable;
use IteratorAggregate;

/**
 * A collection of items with various methods to operate on them with.
 */
class Collection extends LaravelCollection {

	/**
     * Create a new collection.
	 *
	 * @see https://github.com/laravel/framework/blob/6.x/src/Illuminate/Support/Collection.php#L22-L31
     *
     * @param  mixed  $items
     * @return void
     */
	public function __construct($items = []) {

		parent::__construct($this->getArrayableItems($items));
	}

	/**
	 * Results array of items from Collection or Arrayable.
	 *
	 * @see https://github.com/laravel/framework/blob/6.x/src/Illuminate/Support/Traits/EnumeratesValues.php#L799-L822
	 *
	 * @param  mixed  $items
	 * @return array
	 */
	protected function getArrayableItems($items)
	{
		if (is_array($items)) {
			return $items;
		}

		if (!is_object($items)) {
			return (array) $items;
		}

		if (method_exists($items, 'all')) {
			return $items->all();
		}

		if (method_exists($items, 'toArray')) {
			return $items->toArray();
		}

		if (method_exists($items, 'toJson')) {
			return json_decode($items->toJson(), true);
		}

		if ($items instanceof JsonSerializable) {
			return (array) $items->jsonSerialize();
		} elseif ($items instanceof Traversable || $items instanceof IteratorAggregate) {
			return iterator_to_array($items);
		}

		return (array) $items;
	}

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @see https://github.com/laravel/framework/blob/6.x/src/Illuminate/Support/Collection.php#L680-L693
     *
     * @param  callable  $callback
     * @return static
     */
	public function mapWithKeys(callable $callback)
	{
		$result = [];

		foreach ($this->items as $key => $value) {
			$assoc = $callback($value, $key);

			foreach ($assoc as $mapKey => $mapValue) {
				$result[$mapKey] = $mapValue;
			}
		}

		return new static($result);
	}

	/**
     * Concatenate values of a given key as a string.
     *
     * @param  string  $value
     * @param  string  $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
		$first = $this->first();

        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
		}

        return implode($value, $this->items);
	}

	public function first(callable $callback = null, $default = null)
    {
        $array = $this->items;

        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }
            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    public function filter(Closure $callback = null)
	{
		return new static(array_filter($this->items, $callback));
	}
}
