<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Real journal entry reversal — a genuine business need (mistakes
    // happen) this codebase's manual-entry-only Accounting engine has
    // been missing since the Foundation of this module. A reversed
    // entry is never deleted or edited in place (that would break the
    // audit trail); it's marked reversed and linked to the new
    // reversing entry that actually corrects the books.
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->boolean('is_reversed')->default(false)->after('source_id');
            $table->uuid('reversed_by_entry_id')->nullable()->after('is_reversed');
            $table->foreign('reversed_by_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['reversed_by_entry_id']);
            $table->dropColumn(['is_reversed', 'reversed_by_entry_id']);
        });
    }
};
