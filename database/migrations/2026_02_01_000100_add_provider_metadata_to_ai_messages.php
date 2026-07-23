<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // AI Assistant completion: real LLM provider integration is new
    // this sprint (see AiAssistantService and the new App\Services\Ai
    // namespace). These two nullable columns record which provider
    // and model actually generated an assistant reply — null on a
    // message means it came from the deterministic keyword-grounded
    // fallback (no LLM configured, or the LLM call failed and the
    // assistant degraded gracefully), not a data gap.
    public function up(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->string('provider', 30)->nullable()->after('content');
            $table->string('model', 60)->nullable()->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropColumn(['provider', 'model']);
        });
    }
};
