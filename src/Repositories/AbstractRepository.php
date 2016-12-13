<?php

/*
 * This file is part of Laravel Credentials.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\Credentials\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Factory;

/**
 * This is the abstract repository class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
abstract class AbstractRepository
{
    use BaseRepositoryTrait;


    /**
     * The max users per page when displaying a paginated index.
     *
     * @var int
     */
    public static $paginate = 20;

    /**
     * The direction to order by when displaying an index.
     *
     * @var string
     */
    public static $sort = 'asc';

    /**
     * The model to provide.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The validator factory instance.
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * Create a new instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Validation\Factory      $validator
     *
     * @return void
     */
    public function __construct(Model $model, Factory $validator)
    {
        $this->model = $model;
        $this->validator = $validator;

        $this->setPagination();
        $this->setSort();
    }

    /**
     * Return the model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Return the validator factory instance.
     *
     * @return \Illuminate\Validation\Factory
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Set max users per page when displaying a paginated index.
     */
    public function setPagination()
    {
        $this::$paginate = config('credentials.paginate');
    }

    /**
     * Set direction to order by when displaying an index.
     */
    public function setSort()
    {
        $this::$sort = config('credentials.sort');
    }
}