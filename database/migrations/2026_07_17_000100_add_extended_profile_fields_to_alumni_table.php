<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('alumni')) {
            return;
        }

        $addTanggalLahir = !Schema::hasColumn('alumni', 'tanggal_lahir');
        $addAgama = !Schema::hasColumn('alumni', 'agama');
        $addNamaAyah = !Schema::hasColumn('alumni', 'nama_ayah');
        $addNamaIbu = !Schema::hasColumn('alumni', 'nama_ibu');
        $addAlamat = !Schema::hasColumn('alumni', 'alamat');

        if (!($addTanggalLahir || $addAgama || $addNamaAyah || $addNamaIbu || $addAlamat)) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table) use (
            $addTanggalLahir,
            $addAgama,
            $addNamaAyah,
            $addNamaIbu,
            $addAlamat
        ): void {
            if ($addTanggalLahir) {
                $table->date('tanggal_lahir')->nullable()->after('jenis_kelamin');
            }

            if ($addAgama) {
                $table->string('agama')->nullable()->after('tanggal_lahir');
            }

            if ($addNamaAyah) {
                $table->string('nama_ayah')->nullable()->after('agama');
            }

            if ($addNamaIbu) {
                $table->string('nama_ibu')->nullable()->after('nama_ayah');
            }

            if ($addAlamat) {
                $table->text('alamat')->nullable()->after('kontak');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('alumni')) {
            return;
        }

        $dropTanggalLahir = Schema::hasColumn('alumni', 'tanggal_lahir');
        $dropAgama = Schema::hasColumn('alumni', 'agama');
        $dropNamaAyah = Schema::hasColumn('alumni', 'nama_ayah');
        $dropNamaIbu = Schema::hasColumn('alumni', 'nama_ibu');
        $dropAlamat = Schema::hasColumn('alumni', 'alamat');

        if (!($dropTanggalLahir || $dropAgama || $dropNamaAyah || $dropNamaIbu || $dropAlamat)) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table) use (
            $dropTanggalLahir,
            $dropAgama,
            $dropNamaAyah,
            $dropNamaIbu,
            $dropAlamat
        ): void {
            $columns = [];

            if ($dropTanggalLahir) {
                $columns[] = 'tanggal_lahir';
            }

            if ($dropAgama) {
                $columns[] = 'agama';
            }

            if ($dropNamaAyah) {
                $columns[] = 'nama_ayah';
            }

            if ($dropNamaIbu) {
                $columns[] = 'nama_ibu';
            }

            if ($dropAlamat) {
                $columns[] = 'alamat';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
