    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('penyewaan', function (Blueprint $table) {

        $table->id('penyewaan_id');

        $table->foreignId('penyewaan_pelanggan_id')
            ->constrained('pelanggan','pelanggan_id')
            ->cascadeOnDelete();

        $table->date('penyewaan_tglsewa');

        $table->date('penyewaan_tglkembali');

        $table->enum('penyewaan_sttspembayaran',[
            'Lunas',
            'Belum Dibayar',
            'DP'
        ])->default('Belum Dibayar');

        $table->enum('penyewaan_sttskembali',[
            'Sudah Kembali',
            'Belum Kembali'
        ])->default('Belum Kembali');

        $table->integer('penyewaan_totalharga');

        $table->timestamps();

    });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('penyewaan');
        }
    };
