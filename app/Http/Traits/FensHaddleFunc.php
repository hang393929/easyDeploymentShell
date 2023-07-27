<?php
namespace App\Http\Traits;

use App\Http\Helper\Util;
use App\Http\Repository\Fens\AgeRepository;
use App\Http\Repository\Fens\CityRepository;
use App\Http\Repository\Fens\AreaRepository;
use App\Http\Repository\Fens\GenderRepository;
use App\Http\Repository\Fens\InterestRepository;
use App\Http\Repository\Fens\EducationRepository;

trait FensHaddleFunc
{
    /**
     * 年龄数据处理
     *
     * @param int $fensDrawId
     * @param array $data
     * @return void
     */
    public static function haddleAge(int $fensDrawId, array $data = [])
    {
        // 获取已存在的粉丝数据以range为键
        $fens = app(AgeRepository::class)->getAgeByDrawId($fensDrawId, ['id', 'range'])->keyBy('range')->toArray();
        // 编排待处理数据，并把range作为二维数组的key
        $data = Util::arrayKeyBy($data, 'range');
        // 获取交集，作为更新,如果数据库中不存在，则全为新增
        if (!empty($fens)) {
            $update = array_intersect_key($data, $fens);
        }
        // 获取新增的数据,如果没有需要更新的，则全量新增
        if (isset($update) && !empty($update)) {
            $keysToRemove = array_keys($update);
            $insert       = array_diff_key($data, array_flip($keysToRemove));
        } else {
            $insert = $data;
        }

        // 更新
        empty($insert) ?: app(AgeRepository::class)->batchInsert($insert, $fensDrawId);
        // 新增
        empty($update) ?: app(AgeRepository::class)->batchUpdate($update, $fensDrawId);
    }

    /**
     * 地区数据处理
     *
     * @param int $fensDrawId
     * @param array $data
     * @return void
     */
    public static function haddleArea(int $fensDrawId, array $data = [])
    {
        $areas = app(AreaRepository::class)->getAreaByDrawId($fensDrawId, ['area_name'])->pluck('area_name')->toArray();

        $data  = Util::arrayKeyBy($data, 'name');
        if (!empty($areas)) {
            $update = array_intersect_key($data, array_flip($areas));
        }
        if (isset($update) && !empty($update)) {
            $keysToRemove = array_keys($update);
            $insert       = array_diff_key($data, array_flip($keysToRemove));
        } else {
            $insert       = $data;
        }

        // 更新
        empty($insert) ?: app(AreaRepository::class)->batchInsert($insert, $fensDrawId);
        // 新增
        empty($update) ?: app(AreaRepository::class)->batchUpdate($update, $fensDrawId);
    }

    /**
     * 城市数据处理
     *
     * @param int $fensDrawId
     * @param array $data
     * @return void
     */
    public static function haddleCity(int $fensDrawId, array $data = [])
    {
        $citys = app(CityRepository::class)->getCityByDrawId($fensDrawId, ['city_name'])->pluck('city_name')->toArray();
        $data  = Util::arrayKeyBy($data, 'name');
        if (!empty($citys)) {
            $update = array_intersect_key($data, array_flip($citys));
        }
        if (isset($update) && !empty($update)) {
            $keysToRemove = array_keys($update);
            $insert       = array_diff_key($data, array_flip($keysToRemove));
        } else {
            $insert       = $data;
        }

        // 更新
        empty($insert) ?: app(CityRepository::class)->batchInsert($insert, $fensDrawId);
        // 新增
        empty($update) ?: app(CityRepository::class)->batchUpdate($update, $fensDrawId);
    }

    /**
     * 学历数据处理
     *
     * @param int $fensDrawId
     * @param array $data
     * @return void
     */
    public static function haddleEducation(int $fensDrawId, array $data = [])
    {
        $educations = app(EducationRepository::class)->getEducationByDrawId($fensDrawId, ['name'])->pluck('name');
        // 二次过滤数据库中education_id为0及重复的
        $educations = $educations->filter(function ($value) {
            return $value !== 0;
        })->unique()->toArray();

        $data = Util::arrayKeyBy($data, 'name');
        if (!empty($educations)) {
            $update = array_intersect_key($data, array_flip($educations));
        }
        if (isset($update) && !empty($update)) {
            $keysToRemove = array_keys($update);
            $insert       = array_diff_key($data, array_flip($keysToRemove));
        } else {
            $insert       = $data;
        }

        // 更新
        empty($insert) ?: app(EducationRepository::class)->batchInsert($insert, $fensDrawId);
        // 新增
        empty($update) ?: app(EducationRepository::class)->batchUpdate($update, $fensDrawId);
    }

    /**
     * 性别数据处理
     *
     * @param int $fensDrawId
     * @param array $data
     * @return void
     */
    public static function haddleGender(int $fensDrawId, array $data = [])
    {
        $genders = app(GenderRepository::class)->getGenderByDrawId($fensDrawId, ['type'])->pluck('type')->toArray();
        $data    = Util::arrayKeyBy($data, 'type');
        if (!empty($genders)) {
            $update = array_intersect_key($data, array_flip($genders));
        }
        if (isset($update) && !empty($update)) {
            $keysToRemove = array_keys($update);
            $insert       = array_diff_key($data, array_flip($keysToRemove));
        } else {
            $insert       = $data;
        }

        // 更新
        empty($insert) ?: app(GenderRepository::class)->batchInsert($insert, $fensDrawId);
        // 新增
        empty($update) ?: app(GenderRepository::class)->batchUpdate($update, $fensDrawId);
    }

    /**
     * 兴趣爱好数据处理
     *
     * @param int $fensDrawId
     * @param array $data
     * @return void
     */
    public static function haddleInterest(int $fensDrawId, array $data = [])
    {
        $interests = app(InterestRepository::class)->getInterestByDrawId($fensDrawId, ['name'])->pluck('name')->toArray();
        $data      = Util::arrayKeyBy($data, 'name');
        if (!empty($interests)) {
            $update = array_intersect_key($data, array_flip($interests));
        }
        if (isset($update) && !empty($update)) {
            $keysToRemove = array_keys($update);
            $insert       = array_diff_key($data, array_flip($keysToRemove));
        } else {
            $insert       = $data;
        }

        // 新增
        empty($insert) ?: app(InterestRepository::class)->batchInsert(array_values($insert), $fensDrawId);
        // 更新
        empty($update) ?: app(InterestRepository::class)->batchUpdate($update, $fensDrawId);
    }
}
