<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Model\User;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->nullable()->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create("users", function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("role_id")->nullable();
            $table->string("name")->nullable();
            $table->string("username")->nullable()->unique();
            $table->string("email")->nullable()->unique();
            $table->string("mobile", 10)->nullable()->unique();
            $table->string("password", 60)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create("user_tokens", function(Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("user_id")->nullable()->unsigned();
            $table->string("type")->nullable();
            $table->timestamp("expiry")->nullable();
            $table->timestamp("locked_at")->nullable();
            $table->timestamp("last_seen")->nullable();
            $table->ipAddress("ip_address")->nullable();
            $table->text("user_agent")->nullable();
            $table->text("meta")->nullable();
            $table->timestamps();
            $table->softDeletes();
//            $table->index("user_id");
            $table->foreign("user_id")->references("id")->on("users")->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }

}
