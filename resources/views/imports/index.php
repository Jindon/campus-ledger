<h1 class="text-2xl font-semibold mb-6">Imports</h1>

<div class="bg-white rounded-lg border border-slate-200 p-5 mb-8">
    <h2 class="text-lg font-medium mb-3">Import CSV</h2>
    <form action="/imports" method="post" enctype="multipart/form-data" data-loading-text="Importing…" class="flex items-center gap-3">
        <input type="file" name="csv" accept=".csv,text/csv" required
               class="text-sm border border-slate-300 rounded-md px-3 py-2 flex-1">
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed">
            Start Import
        </button>
    </form>
</div>
