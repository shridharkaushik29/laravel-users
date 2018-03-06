<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable("users")) {
            Schema::create("users", function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string("name")->nullable();
                $table->string("username")->nullable()->unique();
                $table->string("email")->nullable()->unique();
                $table->string("mobile", 10)->nullable()->unique();
                $table->string("password", 60)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable("users_tokens")) {
            Schema::create("users_tokens", function(Blueprint $table) {
                $table->bigIncrements("id");
                $table->bigInteger("user_id")->unsigned();
                $table->index("user_id");
                $table->timestamp("expiry")->nullable();
                $table->timestamp("locked_at")->nullable();
                $table->timestamp("last_seen")->nullable();
                $table->ipAddress("ip_address")->nullable();
                $table->text("user_agent")->nullable();
                $table->text("meta")->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->foreign("user_id")->references("id")->on("users")->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users_tokens');
        Schema::dropIfExists('users');
    }

}
