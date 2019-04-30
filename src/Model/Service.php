<?php

namespace hiqdev\recon\core\Model;

use yii\base\Model;

/**
 * Class Service
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 *
 * @property-read string $name
 * @property-read string $soft
 * @property-read string $ip
 * @property-read string $bin
 * @property-read string $etc
 */
class Service extends Model
{
    public $name;
    public $soft;
    public $ip;
    public $bin;
    public $etc;

    public function attributes()
    {
        return [
            'name',
            'soft',
            'ip',
            'bin',
            'etc',
        ];
    }

    public function rules()
    {
        return [
            [['ip'], 'ip'],
            [['soft', 'name', 'bin', 'etc'], 'string'],
            [['name'], 'required'],
        ];
    }
}
