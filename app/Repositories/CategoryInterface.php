<?php
namespace App\Repositories;
interface CategoryInterface extends RepositoryInterface{
    public static function getCategory($filter);
    public static function getAll($filter);
}