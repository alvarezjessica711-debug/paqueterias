<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $codigosConocidos = [
            'Torre 1|301' => 'A1',
            'Torre 1|402' => 'A2',
            'Torre 2|105' => 'B1',
            'Torre 2|203' => 'B2',
            'Torre 2|204' => 'C1',
        ];

        $ubicaciones = DB::table('apartamentos')->get(['id', 'torre', 'numero']);
        foreach ($codigosConocidos as $ubicacion => $codigo) {
            $coincidencias = $ubicaciones->filter(fn (object $casa): bool => ($codigosConocidos[$casa->torre.'|'.$casa->numero] ?? null) === $codigo);
            if ($coincidencias->count() > 1) {
                throw new RuntimeException("No se puede asignar {$codigo}: más de una vivienda coincide con la ubicación confirmada.");
            }
        }

        Schema::table('apartamentos', function (Blueprint $table): void {
            $table->string('codigo')->nullable();
            $table->text('ubicacion_anterior')->nullable();
        });

        foreach (DB::table('apartamentos')->orderBy('id')->get(['id', 'torre', 'numero']) as $casa) {
            DB::table('apartamentos')->where('id', $casa->id)->update([
                'codigo' => $codigosConocidos[$casa->torre.'|'.$casa->numero] ?? 'LEG-'.$casa->id,
                'ubicacion_anterior' => json_encode([
                    'torre' => $casa->torre,
                    'numero' => $casa->numero,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
        }

        Schema::table('apartamentos', function (Blueprint $table): void {
            $table->dropUnique('apartamentos_torre_numero_unique');
            $table->dropColumn(['torre', 'numero']);
            $table->unique('codigo');
        });

    }

    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table): void {
            $table->string('torre')->nullable();
            $table->string('numero')->nullable();
        });

        foreach (DB::table('apartamentos')->orderBy('id')->get(['id', 'codigo', 'ubicacion_anterior']) as $casa) {
            $ubicacion = $casa->ubicacion_anterior
                ? json_decode($casa->ubicacion_anterior, true, flags: JSON_THROW_ON_ERROR)
                : null;

            if (is_array($ubicacion) && isset($ubicacion['torre'], $ubicacion['numero'])) {
                $torre = $ubicacion['torre'];
                $numero = $ubicacion['numero'];
            } elseif (preg_match('/^([A-F])([1-9][0-9]*)$/', $casa->codigo, $partes) === 1) {
                $torre = $partes[1];
                $numero = $partes[2];
            } else {
                $torre = 'LEG';
                $numero = (string) $casa->id;
            }

            DB::table('apartamentos')->where('id', $casa->id)->update([
                'torre' => $torre,
                'numero' => $numero,
            ]);
        }

        Schema::table('apartamentos', function (Blueprint $table): void {
            $table->dropUnique('apartamentos_codigo_unique');
            $table->dropColumn(['codigo', 'ubicacion_anterior']);
            $table->unique(['torre', 'numero']);
        });
    }

};
