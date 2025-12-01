import { DNSRecord } from './types.ts';

const DNS_TYPES = {
  A: 1,
  NS: 2,
  CNAME: 5,
  SOA: 6,
  PTR: 12,
  MX: 15,
  TXT: 16,
  AAAA: 28,
} as const;

export async function queryDNS(domain: string, type: string): Promise<DNSRecord[]> {
  try {
    const response = await fetch(`https://cloudflare-dns.com/dns-query?name=${encodeURIComponent(domain)}&type=${type}`, {
      headers: {
        'Accept': 'application/dns-json',
      },
    });

    if (!response.ok) {
      throw new Error('DNS查询失败');
    }

    const data = await response.json();
    
    if (!data.Answer) {
      return [];
    }

    return data.Answer.map((answer: any) => ({
      type: answer.type in DNS_TYPES ? Object.keys(DNS_TYPES)[Object.values(DNS_TYPES).indexOf(answer.type)] : 'UNKNOWN',
      name: answer.name,
      value: answer.data,
      ttl: answer.TTL,
    }));
  } catch (error) {
    console.error('DNS查询错误:', error);
    throw new Error('DNS查询失败，请检查域名是否正确');
  }
}