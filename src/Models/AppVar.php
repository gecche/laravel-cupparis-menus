<?php namespace Gecche\Cupparis\Menus\Models;

use Cupparis\Ardent\Ardent as Ardent;

class AppVar extends Ardent {

    //public $timestamps = false;

    protected $table = 'vars';
    
    protected $fillable = array('id', 'valore');
    
    public static $relationsData = array(
        //'address' => array(self::HAS_ONE, 'Address'),
        //'orders'  => array(self::HAS_MANY, 'Order'),
        //'user' => array(self::BELONGS_TO, 'User'),
    );

    public static function initializeVar($id,$value = 0) {
        $appvar = static::firstOrNew(['id'=>$id]);
        if ($appvar->value) {
            return $appvar;
        }
        $appvar->value = 0;
        $appvar->save();
        return $appvar;
    }



}
