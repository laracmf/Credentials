<?php

/**
 * This file is part of Laravel Credentials by Graham Campbell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GrahamCampbell\Credentials\Presenters;

/**
 * This is the author presenter trait.
 *
 * @package    Laravel-Credentials
 * @author     Graham Campbell
 * @copyright  Copyright 2013-2014 Graham Campbell
 * @license    https://github.com/GrahamCampbell/Laravel-Credentials/blob/master/LICENSE.md
 * @link       https://github.com/GrahamCampbell/Laravel-Credentials
 */
trait AuthorPresenterTrait
{
    /**
     * Get the author's name.
     *
     * @return string
     */
    public function author()
    {
        $user = $this->resource->user()
            ->cacheDriver('array')->rememberForever()
            ->first(array('first_name', 'last_name'));

        return $user->first_name.' '.$user->last_name;
    }
}
