<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'no_telepon')) {
                $table->string('no_telepon', 30)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'no_whatsapp')) {
                $table->string('no_whatsapp', 30)->nullable()->after('no_telepon');
            }
            if (! Schema::hasColumn('users', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('no_whatsapp');
            }
            if (! Schema::hasColumn('users', 'nisn')) {
                $table->string('nisn', 10)->nullable()->index()->after('tanggal_lahir');
            }
            if (! Schema::hasColumn('users', 'nis')) {
                $table->string('nis', 30)->nullable()->after('nisn');
            }
            if (! Schema::hasColumn('users', 'nama_sekolah')) {
                $table->string('nama_sekolah', 150)->nullable()->default('SMK Negeri 5 Surakarta')->after('nis');
            }
            if (! Schema::hasColumn('users', 'tahun_ajaran')) {
                $table->string('tahun_ajaran', 30)->nullable()->default('2026/2027')->after('nama_sekolah');
            }
            if (! Schema::hasColumn('users', 'nama_kelas')) {
                $table->string('nama_kelas', 60)->nullable()->after('tahun_ajaran');
            }
            if (! Schema::hasColumn('users', 'alamat')) {
                $table->text('alamat')->nullable()->after('nama_kelas');
            }
            if (! Schema::hasColumn('users', 'verification_code')) {
                $table->string('verification_code', 100)->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'verification_code_expires_at')) {
                $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE peminjamans MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'menunggu_approval'");
        }

        Schema::table('peminjamans', function (Blueprint $table) {
            if (! Schema::hasColumn('peminjamans', 'kode_transaksi')) {
                $table->string('kode_transaksi', 40)->nullable()->index()->after('id');
            }
            if (! Schema::hasColumn('peminjamans', 'prioritas')) {
                $table->string('prioritas', 20)->default('normal')->index()->after('status');
            }
            if (! Schema::hasColumn('peminjamans', 'keperluan')) {
                $table->string('keperluan', 100)->nullable()->after('prioritas');
            }
            if (! Schema::hasColumn('peminjamans', 'catatan_peminjam')) {
                $table->text('catatan_peminjam')->nullable()->after('keperluan');
            }
            if (! Schema::hasColumn('peminjamans', 'catatan_pustakawan')) {
                $table->text('catatan_pustakawan')->nullable()->after('catatan_peminjam');
            }
            if (! Schema::hasColumn('peminjamans', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('catatan_pustakawan');
            }
            if (! Schema::hasColumn('peminjamans', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Isi kode transaksi untuk data lama
        $existingLoans = DB::table('peminjamans')->whereNull('kode_transaksi')->get();
        foreach ($existingLoans as $loan) {
            $datePart = str_replace('-', '', substr((string) ($loan->tanggal_pinjam ?? date('Y-m-d')), 0, 10));
            $code = 'TRX-'.$datePart.'-'.str_pad((string) $loan->id, 3, '0', STR_PAD_LEFT);
            DB::table('peminjamans')->where('id', $loan->id)->update(['kode_transaksi' => $code]);
        }

        if (! Schema::hasTable('admin_tasks')) {
            Schema::create('admin_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('judul');
                $table->string('deskripsi')->nullable();
                $table->string('kategori_tugas', 50)->default('Manajemen User');
                $table->string('prioritas', 20)->default('penting');
                $table->unsignedTinyInteger('progres')->default(0);
                $table->boolean('is_completed')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_tasks');

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'kode_transaksi',
                'prioritas',
                'keperluan',
                'catatan_peminjam',
                'catatan_pustakawan',
                'approved_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'no_telepon',
                'no_whatsapp',
                'tanggal_lahir',
                'nisn',
                'nis',
                'nama_sekolah',
                'tahun_ajaran',
                'nama_kelas',
                'alamat',
                'verification_code',
                'verification_code_expires_at',
                'is_active',
            ]);
        });
    }
};
