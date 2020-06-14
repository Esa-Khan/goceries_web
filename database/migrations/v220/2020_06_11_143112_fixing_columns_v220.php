<?php
/**
 * File name: 2020_06_11_143112_fixing_columns_v220.php
 * Last modified: 2020.06.11 at 16:02:18
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixingColumnsV220 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!\Doctrine\DBAL\Types\Type::hasType('double')) {
            \Doctrine\DBAL\Types\Type::addType('double', \Doctrine\DBAL\Types\FloatType::class);
            \Doctrine\DBAL\Types\Type::addType('tinyinteger', \Doctrine\DBAL\Types\SmallIntType::class);
            \Doctrine\DBAL\Types\Type::addType('timestamp', \Doctrine\DBAL\Types\DateTimeType::class);
        }

        if (Schema::hasTable('foods')) {
            Schema::table('foods', function (Blueprint $table) {
                $table->double('weight', 9, 2)->nullable()->default(0)->change();
            });
        }

        if (Schema::hasTable('restaurants')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
                $table->string('address', 255)->nullable()->change();
                $table->string('phone', 50)->nullable()->change();
                $table->string('mobile', 50)->nullable()->change();
                $table->text('information')->nullable()->change();
                $table->double('admin_commission', 8, 2)->nullable()->default(0)->change();
                $table->double('delivery_fee', 8, 2)->nullable()->default(0)->change();
                $table->double('delivery_range', 8, 2)->nullable()->default(0)->change();//added
                $table->double('default_tax', 8, 2)->nullable()->default(0)->change(); // //added
                $table->boolean('closed')->nullable()->default(0)->change(); // //added
                $table->boolean('available_for_delivery')->nullable()->default(1)->change(); //added
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
