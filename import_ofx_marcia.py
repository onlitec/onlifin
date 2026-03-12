#!/usr/bin/env python3
"""
Script para importar transações OFX da conta CORA da Marcia no Onlifin
"""
import re
import json
import requests
from datetime import datetime
from typing import List, Dict, Optional

# Configurações
API_URL = "https://onlifin.onlitec.com.br/api/rest/v1"
OFX_FILE = "/home/alfreire/docs/34-565-338-marcia-aparecida-domingos-freire_01012026_a_04032026_97a7d4bd.ofx"

# Credenciais (usuário deve fazer login primeiro)
EMAIL = "onlifinadmin@miaoda.com"
PASSWORD = "Onlifin@2024"

class OFXParser:
    """Parser simples para arquivos OFX"""
    
    def __init__(self, filepath: str):
        self.filepath = filepath
        self.transactions = []
        
    def parse(self) -> List[Dict]:
        """Parse do arquivo OFX"""
        with open(self.filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Extrair informações da conta
        bank_id = self._extract_tag(content, 'BANKID')
        branch_id = self._extract_tag(content, 'BRANCHID')
        account_id = self._extract_tag(content, 'ACCTID')
        
        print(f"📊 Conta: Banco {bank_id} - Agência {branch_id} - Conta {account_id}")
        
        # Extrair transações
        transactions = re.findall(r'<STMTTRN>(.*?)</STMTTRN>', content, re.DOTALL)
        
        for trx in transactions:
            transaction = {
                'type': self._extract_tag(trx, 'TRNTYPE'),
                'date': self._parse_date(self._extract_tag(trx, 'DTPOSTED')),
                'amount': float(self._extract_tag(trx, 'TRNAMT')),
                'fitid': self._extract_tag(trx, 'FITID'),
                'memo': self._extract_tag(trx, 'MEMO'),
            }
            
            # Categorizar transação
            transaction['category'] = self._categorize(transaction)
            transaction['description'] = self._clean_description(transaction['memo'])
            
            self.transactions.append(transaction)
        
        print(f"✅ {len(self.transactions)} transações encontradas")
        return self.transactions
    
    def _extract_tag(self, content: str, tag: str) -> str:
        """Extrai valor de uma tag OFX"""
        match = re.search(f'<{tag}>(.*?)(?:<|$)', content)
        return match.group(1).strip() if match else ''
    
    def _parse_date(self, date_str: str) -> str:
        """Converte data OFX para formato ISO"""
        # Formato: 20260301000000[0:GMT]
        if not date_str:
            return ''
        date_part = date_str[:8]  # YYYYMMDD
        return f"{date_part[:4]}-{date_part[4:6]}-{date_part[6:8]}"
    
    def _clean_description(self, memo: str) -> str:
        """Limpa e melhora a descrição"""
        # Remove espaços extras
        memo = re.sub(r'\s+', ' ', memo).strip()
        
        # Remove traços finais
        memo = re.sub(r'\s*-\s*$', '', memo)
        
        return memo
    
    def _categorize(self, transaction: Dict) -> str:
        """Categoriza a transação baseado no memo"""
        memo = transaction['memo'].upper()
        amount = transaction['amount']
        
        if amount > 0:  # RECEITAS
            if 'QUALLIT' in memo or 'HI COMERCIO' in memo or 'HI ENGENHARIA' in memo:
                return 'Salário'
            elif 'HELPSEG' in memo or 'GERENCIAL' in memo or 'GRAMA VALE' in memo:
                return 'Pagamento Recebido'
            elif 'DEVOLUCAO' in memo or 'DEVOLUÇÃO' in memo:
                return 'Devolução'
            else:
                return 'Transferência Recebida'
        
        else:  # DESPESAS
            if 'TENDA' in memo or 'SENDAS' in memo or 'CARREFOUR' in memo or 'SUPERMERCADO' in memo or 'MERCADO' in memo:
                return 'Supermercado'
            elif 'POSTO' in memo or 'COMBUSTIVEL' in memo:
                return 'Combustível'
            elif 'IFOOD' in memo or 'RESTAURANTE' in memo or 'BURGUER' in memo or 'ACAI' in memo:
                return 'Restaurante'
            elif 'RAIA' in memo or 'DROGASIL' in memo or 'FARMACIA' in memo or 'DROGARIA' in memo:
                return 'Farmácia'
            elif 'CLARO' in memo or 'TELEFONICA' in memo or 'VIVO' in memo:
                return 'Telefone'
            elif 'SABESP' in memo or 'AGUA' in memo or 'LUZ' in memo:
                return 'Água/Luz'
            elif 'MARKETPLACE' in memo or 'SHPP' in memo or 'SHOPEE' in memo:
                return 'Compras Online'
            elif 'KABUM' in memo or 'PICHAU' in memo or 'INFORMATICA' in memo:
                return 'Eletrônicos'
            elif 'HAVAN' in memo or 'LEROY' in memo or 'SODIMAC' in memo:
                return 'Varejo'
            elif 'ASSOCIACAO' in memo or 'ESPORTIVA' in memo:
                return 'Lazer'
            elif 'HOSTGATOR' in memo or 'HOSTING' in memo:
                return 'Serviços Online'
            elif 'TRANSF PIX ENVIADA' in memo:
                if 'BEATRIZ' in memo or 'GEOVANNA' in memo or 'MIGUEL' in memo or 'ALESSANDRO' in memo:
                    return 'Transferência Família'
                elif 'MARCIA APARECIDA' in memo:
                    return 'Transferência Entre Contas'
                else:
                    return 'Transferência Enviada'
            elif 'SERV COBRANCAS' in memo or 'JUAN ESTEBAN' in memo or 'MARLON ANDRES' in memo or 'ASDRUBAL' in memo:
                return 'Serviços/Cobranças'
            elif 'ESTACIONAMENTO' in memo or 'PARKING' in memo:
                return 'Estacionamento'
            else:
                return 'Outros'

class OnlifinAPI:
    """Cliente para API do Onlifin"""
    
    def __init__(self, base_url: str):
        self.base_url = base_url
        self.token = None
        self.user_id = None
        self.session = requests.Session()
    
    def login(self, email: str, password: str) -> bool:
        """Faz login e obtém token"""
        try:
            response = self.session.post(
                f"{self.base_url}/rpc/login",
                json={
                    "p_email": email,
                    "p_password": password
                },
                headers={
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                }
            )
            
            if response.status_code == 200:
                self.token = response.text.strip('"')
                self.session.headers.update({
                    "Authorization": f"Bearer {self.token}"
                })
                
                # Decodificar JWT para obter user_id
                import base64
                try:
                    payload = self.token.split('.')[1]
                    payload += '=' * (4 - len(payload) % 4)
                    decoded = base64.b64decode(payload)
                    import json as json_lib
                    token_data = json_lib.loads(decoded)
                    self.user_id = token_data.get('sub') or token_data.get('user_id')
                    print(f"✅ Login realizado com sucesso (User ID: {self.user_id})")
                except Exception as e:
                    print(f"⚠️  Não foi possível extrair user_id do token: {e}")
                
                return True
            else:
                print(f"❌ Erro no login: {response.status_code} - {response.text}")
                return False
        except Exception as e:
            print(f"❌ Erro ao fazer login: {e}")
            return False
    
    def get_people(self) -> List[Dict]:
        """Lista todas as pessoas"""
        try:
            response = self.session.get(f"{self.base_url}/people")
            if response.status_code == 200:
                return response.json()
            else:
                print(f"❌ Erro ao buscar pessoas: {response.status_code}")
                return []
        except Exception as e:
            print(f"❌ Erro: {e}")
            return []
    
    def get_accounts(self, person_id: str) -> List[Dict]:
        """Lista contas de uma pessoa"""
        try:
            response = self.session.get(
                f"{self.base_url}/accounts",
                params={"person_id": f"eq.{person_id}"}
            )
            if response.status_code == 200:
                return response.json()
            else:
                print(f"❌ Erro ao buscar contas: {response.status_code}")
                return []
        except Exception as e:
            print(f"❌ Erro: {e}")
            return []
    
    def create_account(self, person_id: str, account_data: Dict) -> Optional[Dict]:
        """Cria uma nova conta"""
        try:
            response = self.session.post(
                f"{self.base_url}/accounts",
                json=account_data,
                headers={"Prefer": "return=representation"}
            )
            if response.status_code == 201:
                return response.json()[0] if response.json() else None
            else:
                print(f"❌ Erro ao criar conta: {response.status_code} - {response.text}")
                return None
        except Exception as e:
            print(f"❌ Erro: {e}")
            return None
    
    def create_transaction(self, transaction_data: Dict) -> bool:
        """Cria uma transação"""
        try:
            response = self.session.post(
                f"{self.base_url}/transactions",
                json=transaction_data,
                headers={"Prefer": "return=minimal"}
            )
            if response.status_code == 201:
                return True
            else:
                print(f"❌ Erro ao criar transação: {response.status_code} - {response.text}")
                return False
        except Exception as e:
            print(f"❌ Erro: {e}")
            return False

def main():
    """Função principal"""
    print("🚀 Iniciando importação de transações OFX para Onlifin")
    print("=" * 60)
    
    # 1. Parse do arquivo OFX
    print("\n📄 Lendo arquivo OFX...")
    parser = OFXParser(OFX_FILE)
    transactions = parser.parse()
    
    # Mostrar resumo
    credits = [t for t in transactions if t['amount'] > 0]
    debits = [t for t in transactions if t['amount'] < 0]
    
    print(f"\n📊 Resumo:")
    print(f"   Receitas: {len(credits)} transações - R$ {sum(t['amount'] for t in credits):,.2f}")
    print(f"   Despesas: {len(debits)} transações - R$ {abs(sum(t['amount'] for t in debits)):,.2f}")
    
    # 2. Conectar à API
    print(f"\n🔐 Conectando à API do Onlifin...")
    api = OnlifinAPI(API_URL)
    
    if not api.login(EMAIL, PASSWORD):
        print("❌ Não foi possível fazer login. Verifique as credenciais.")
        return
    
    # 3. Buscar pessoa Marcia
    print(f"\n👤 Buscando pessoa 'Marcia'...")
    people = api.get_people()
    marcia = None
    
    for person in people:
        if 'MARCIA' in person.get('name', '').upper():
            marcia = person
            print(f"✅ Pessoa encontrada: {person['name']} (ID: {person['id']})")
            break
    
    if not marcia:
        print("❌ Pessoa 'Marcia' não encontrada no sistema.")
        print("   Pessoas disponíveis:")
        for p in people:
            print(f"   - {p.get('name')} (ID: {p.get('id')})")
        return
    
    # 4. Buscar ou criar conta CORA
    print(f"\n🏦 Buscando conta CORA...")
    accounts = api.get_accounts(marcia['id'])
    cora_account = None
    
    for account in accounts:
        if 'CORA' in account.get('name', '').upper():
            cora_account = account
            print(f"✅ Conta encontrada: {account['name']} (ID: {account['id']})")
            break
    
    if not cora_account:
        print("⚠️  Conta CORA não encontrada. Criando...")
        account_data = {
            "person_id": marcia['id'],
            "name": "CORA",
            "type": "checking",
            "bank": "Cora SCD SA",
            "agency": "0001",
            "account_number": "57022454",
            "initial_balance": 0.0,
            "is_active": True
        }
        cora_account = api.create_account(marcia['id'], account_data)
        
        if cora_account:
            print(f"✅ Conta CORA criada com sucesso (ID: {cora_account['id']})")
        else:
            print("❌ Não foi possível criar a conta CORA")
            return
    
    # 5. Importar transações
    print(f"\n💾 Importando {len(transactions)} transações...")
    print("=" * 60)
    
    success_count = 0
    error_count = 0
    
    for i, trx in enumerate(transactions, 1):
        transaction_data = {
            "user_id": api.user_id,
            "account_id": cora_account['id'],
            "person_id": marcia['id'],
            "date": trx['date'],
            "description": trx['description'],
            "amount": abs(trx['amount']),
            "type": "income" if trx['amount'] > 0 else "expense",
            "tags": [trx['category'], f"OFX:{trx['fitid']}"]
        }
        
        if api.create_transaction(transaction_data):
            success_count += 1
            if i % 50 == 0:
                print(f"   ✅ {i}/{len(transactions)} transações importadas...")
        else:
            error_count += 1
            print(f"   ❌ Erro na transação {i}: {trx['description'][:50]}")
    
    # 6. Resumo final
    print("\n" + "=" * 60)
    print("📊 RESUMO DA IMPORTAÇÃO")
    print("=" * 60)
    print(f"✅ Transações importadas com sucesso: {success_count}")
    print(f"❌ Transações com erro: {error_count}")
    print(f"📈 Taxa de sucesso: {(success_count/len(transactions)*100):.1f}%")
    print("\n🎉 Importação concluída!")

if __name__ == "__main__":
    main()
