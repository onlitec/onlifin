<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryCleanupController extends Controller
{
    /**
     * Exibe análise de duplicatas para administradores
     */
    public function analyze()
    {
        $user = Auth::user();
        
        if (!$user->hasPermission('view_all_categories')) {
            abort(403, 'Você não tem permissão para acessar esta funcionalidade.');
        }
        
        $duplicateGroups = $this->findAllDuplicateGroups();
        $stats = $this->calculateCleanupStats($duplicateGroups);
        
        return view('categories.cleanup', compact('duplicateGroups', 'stats'));
    }
    
    /**
     * Executa limpeza de duplicatas
     */
    public function cleanup(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasPermission('delete_all_categories')) {
            abort(403, 'Você não tem permissão para executar limpeza de categorias.');
        }
        
        $dryRun = $request->boolean('dry_run', false);
        
        try {
            DB::beginTransaction();
            
            $result = $this->performCleanup($dryRun);
            
            if (!$dryRun) {
                DB::commit();
                Log::info('Limpeza de categorias executada com sucesso', $result);
            } else {
                DB::rollBack();
            }
            
            $message = $dryRun ? 
                'Análise concluída. Nenhuma alteração foi feita.' :
                "Limpeza concluída! {$result['categories_removed']} categorias removidas, {$result['transactions_moved']} transações consolidadas.";
            
            return redirect()->route('categories.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro na limpeza de categorias: ' . $e->getMessage());
            
            return redirect()->route('categories.index')
                ->with('error', 'Erro ao executar limpeza: ' . $e->getMessage());
        }
    }
    
    /**
     * Encontra todos os grupos de duplicatas
     */
    private function findAllDuplicateGroups()
    {
        return DB::select("
            SELECT 
                TRIM(LOWER(name)) as normalized_name,
                MIN(name) as example_name,
                type,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users,
                GROUP_CONCAT(DISTINCT user_id ORDER BY user_id) as user_ids
            FROM categories
            GROUP BY TRIM(LOWER(name)), type
            HAVING COUNT(*) > 1
            ORDER BY COUNT(*) DESC, MIN(name)
        ");
    }
    
    /**
     * Calcula estatísticas da limpeza
     */
    private function calculateCleanupStats($duplicateGroups)
    {
        $totalCategories = Category::count();
        $totalDuplicates = array_sum(array_column($duplicateGroups, 'count'));
        $categoriesToRemove = 0;
        
        foreach ($duplicateGroups as $group) {
            $categoriesToRemove += ($group->count - 1); // Manter apenas 1 de cada grupo
        }
        
        return [
            'total_categories' => $totalCategories,
            'total_duplicates' => $totalDuplicates,
            'categories_to_remove' => $categoriesToRemove,
            'duplicate_groups_count' => count($duplicateGroups),
            'percentage' => $totalCategories > 0 ? round(($totalDuplicates / $totalCategories) * 100, 1) : 0
        ];
    }
    
    /**
     * Executa a limpeza efetiva
     */
    private function performCleanup($dryRun = false)
    {
        $duplicateGroups = $this->findAllDuplicateGroups();
        
        $categoriesRemoved = 0;
        $transactionsMoved = 0;
        
        foreach ($duplicateGroups as $group) {
            $categories = Category::whereRaw('TRIM(LOWER(name)) = ?', [$group->normalized_name])
                ->where('type', $group->type)
                ->orderBy('id') // Manter a mais antiga
                ->get();
            
            if ($categories->count() <= 1) {
                continue;
            }
            
            $keepCategory = $categories->first();
            $duplicateCategories = $categories->slice(1);
            
            foreach ($duplicateCategories as $duplicate) {
                $transactionCount = Transaction::where('category_id', $duplicate->id)->count();
                
                if ($transactionCount > 0) {
                    $transactionsMoved += $transactionCount;
                    
                    if (!$dryRun) {
                        Transaction::where('category_id', $duplicate->id)
                            ->update(['category_id' => $keepCategory->id]);
                    }
                }
                
                if (!$dryRun) {
                    $duplicate->delete();
                }
                
                $categoriesRemoved++;
            }
        }
        
        return [
            'categories_removed' => $categoriesRemoved,
            'transactions_moved' => $transactionsMoved,
            'duplicate_groups_processed' => count($duplicateGroups)
        ];
    }
} 