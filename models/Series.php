<?php

namespace GinoPane\BlogTaxonomy\Models;


use Model;
use Cms\Classes\Controller;
use GinoPane\BlogTaxonomy\Plugin;
use October\Rain\Database\Traits\Sluggable;
use October\Rain\Database\Traits\Validation;
use RainLab\Blog\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * Class Series
 *
 * @package GinoPane\BlogTaxonomy\Models
 */
class Series extends Model
{
    use Validation;
    use Sluggable;

    const TABLE_NAME = 'ginopane_blogtaxonomy_series';

    /**
     * The database table used by the model
     *
     * @var string
     */
    public $table = self::TABLE_NAME;

    /**
     * Relations
     *
     * @var array
     */
    public $hasMany = [
        'posts' => [
            Post::class,
            'key' => self::TABLE_NAME . "_id"
        ],
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public $rules = [
        'title' => "required|unique:" . self::TABLE_NAME . "|min:3|regex:/^[a-z0-9\- ]+$/i",
        'slug' => "required|unique:" . self::TABLE_NAME . "|min:3|regex:/^[a-z0-9\-]+$/i"
    ];

    /**
     * Validation messages
     *
     * @var array
     */
    public $customMessages = [
        'title.required' => Plugin::LOCALIZATION_KEY . 'lang.form.name_required',
        'title.unique'   => Plugin::LOCALIZATION_KEY . 'lang.form.name_unique',
        'title.regex'    => Plugin::LOCALIZATION_KEY . 'lang.form.name_invalid',
        'title.min'      => Plugin::LOCALIZATION_KEY . 'lang.form.name_too_short',
    ];

    /**
     * The attributes on which the post list can be ordered
     *
     * @var array
     */
    //@todo localize sorting options
    public static $sortingOptions = [
        'title asc' => 'Title (ascending)',
        'title desc' => 'Title (descending)',
        'updated_at asc' => 'Updated (ascending)',
        'updated_at desc' => 'Updated (descending)',
        'published_at asc' => 'Published (ascending)',
        'published_at desc' => 'Published (descending)',
        'random' => 'Random'
    ];

    /**
     * @var array
     */
    protected $slugs = ['slug' => 'title'];

    /**
     * @return mixed
     */
    public function getPostCountAttribute()
    {
        return $this->posts()->isPublished()->count();
    }

    /**
     * Sets the URL attribute with a URL to this object
     *
     * @param string                $pageName
     * @param Controller            $controller
     *
     * @return string
     */
    public function setUrl($pageName, $controller): string
    {
        $params = [
            'slug' => $this->slug,
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }

    /**
     * @param $query
     * @param $options
     * @return mixed
     */
    public function scopeListFrontend($query, $options)
    {
        // Default options
        array_merge(['sort' => 'created_at'], $options);

        // Sorting
        // @see \RainLab\Blog\Models\Post::scopeListFrontEnd()
        if (!is_array($options['sort'])) {
            $options['sort'] = [$options['sort']];
        }

        foreach ($options['sort'] as $sort) {
            if (in_array($sort, array_keys(self::$sortingOptions))) {
                $parts = explode(' ', $sort);
                if (count($parts) < 2) {
                    array_push($parts, 'desc');
                }
                list($sortField, $sortDirection) = $parts;
                if ($sortField == 'random') {
                    $sortField = DB::raw('RAND()');
                }
                $query->orderBy($sortField, $sortDirection);
            }
        }

        return $query->with(
            [
                'posts' => function($query){
                    $query->isPublished();
                }
            ]
        )->get();
    }
}
