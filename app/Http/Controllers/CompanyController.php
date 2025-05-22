<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Setting\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\Setting\EntityType;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Auth::user()->allCompanies();
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'entity_type' => 'required|string',
            'chatbot_enabled' => 'boolean',
        ]);

        $user = Auth::user();
        $personalCompany = $user->personalCompany() === null;

        $company = $user->ownedCompanies()->create([
            'name' => $request->name,
            'personal_company' => $personalCompany,
        ]);

        $profile = $company->profile()->create([
            'email' => $request->email,
            'entity_type' => $request->entity_type,
            'chatbot_enabled' => $request->has('chatbot_enabled'),
        ]);

        $user->switchCompany($company);

        return redirect()->route('companies.index')->with('success', 'Empresa criada com sucesso!');
    }

    public function switch(Company $company)
    {
        if (Auth::user()->switchCompany($company)) {
            return redirect()->back()->with('success', 'Empresa alterada com sucesso!');
        }

        return redirect()->back()->with('error', 'Não foi possível alterar a empresa.');
    }

    public function edit(Company $company)
    {
        if (! Auth::user()->belongsToCompany($company)) {
            return redirect()->route('companies.index')->with('error', 'Não autorizado.');
        }

        $entityTypes = EntityType::cases();
        return view('companies.edit', compact('company', 'entityTypes'));
    }

    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'entity_type' => 'required|string',
            'chatbot_enabled' => 'boolean',
        ]);

        if (! Auth::user()->belongsToCompany($company)) {
            return redirect()->route('companies.index')->with('error', 'Não autorizado.');
        }

        // Atualiza dados da empresa
        $company->update([
            'name' => $request->name,
        ]);

        // Atualiza ou cria perfil
        $profileData = [
            'email' => $request->email,
            'entity_type' => $request->entity_type,
            'chatbot_enabled' => $request->has('chatbot_enabled'),
        ];
        if ($company->profile) {
            $company->profile->update($profileData);
        } else {
            $company->profile()->create($profileData);
        }

        // Associa empresa atual à sessão se o chatbot foi ativado
        if ($request->has('chatbot_enabled')) {
            Auth::user()->switchCompany($company);
        }

        return redirect()->route('companies.index')->with('success', 'Empresa atualizada com sucesso!');
    }

    public function destroy(Company $company)
    {
        if (! Auth::user()->belongsToCompany($company)) {
            return redirect()->route('companies.index')->with('error', 'Não autorizado.');
        }

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Empresa excluída com sucesso!');
    }
} 