import React, { useState } from 'react';
import { Search, Globe, AlertCircle, Terminal, Server } from 'lucide-react';
import { queryDNS } from './dns.ts';
import type { DNSRecord } from './types.ts';

function App() {
  const [domain, setDomain] = useState('');
  const [recordType, setRecordType] = useState('A');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [records, setRecords] = useState<DNSRecord[]>([]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!domain) {
      setError('请输入域名');
      return;
    }
    
    setLoading(true);
    setError('');
    setRecords([]);
    
    try {
      const results = await queryDNS(domain, recordType);
      if (results.length === 0) {
        setError('未找到该域名的记录');
      } else {
        setRecords(results);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : '获取DNS记录失败');
      setRecords([]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-black text-green-400 p-6 relative overflow-hidden">
      {/* 动态背景网格 */}
      <div className="absolute inset-0 bg-[linear-gradient(to_right,#1a1a1a_1px,transparent_1px),linear-gradient(to_bottom,#1a1a1a_1px,transparent_1px)] bg-[size:24px_24px] opacity-30"></div>
      
      <div className="relative max-w-4xl mx-auto">
        <div className="text-center mb-8 relative">
          <div className="inline-block p-4 bg-black border-2 border-green-500 rounded-xl mb-4 relative">
            <Terminal className="w-10 h-10 text-green-500" />
            <div className="absolute -inset-px bg-green-500 opacity-20 rounded-xl animate-pulse"></div>
          </div>
          <h1 className="text-4xl font-bold text-green-500 mb-2 glitch-text">域名侦测系统</h1>
          <p className="text-green-400 font-mono">// 系统就绪 - 等待域名解析指令 //</p>
        </div>

        <div className="bg-gray-900 rounded-lg border border-green-500/30 p-6 mb-6 backdrop-blur-sm relative">
          <div className="absolute top-0 right-0 p-2">
            <Server className="w-5 h-5 text-green-500 animate-pulse" />
          </div>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="flex flex-col md:flex-row gap-4">
              <div className="flex-1">
                <label htmlFor="domain" className="block text-sm font-mono text-green-400 mb-1">
                  目标域名
                </label>
                <input
                  type="text"
                  id="domain"
                  placeholder="输入域名..."
                  value={domain}
                  onChange={(e) => setDomain(e.target.value)}
                  className="w-full px-4 py-2 bg-black border border-green-500/50 rounded-md text-green-400 placeholder-green-700 focus:ring-2 focus:ring-green-500 focus:border-green-500 font-mono"
                  required
                />
              </div>
              <div className="md:w-48">
                <label htmlFor="recordType" className="block text-sm font-mono text-green-400 mb-1">
                  记录类型
                </label>
                <select
                  id="recordType"
                  value={recordType}
                  onChange={(e) => setRecordType(e.target.value)}
                  className="w-full px-4 py-2 bg-black border border-green-500/50 rounded-md text-green-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 font-mono"
                >
                  <option value="A">A记录</option>
                  <option value="AAAA">AAAA记录</option>
                  <option value="CNAME">CNAME记录</option>
                  <option value="MX">MX记录</option>
                  <option value="TXT">TXT记录</option>
                  <option value="NS">NS记录</option>
                </select>
              </div>
            </div>
            <button
              type="submit"
              disabled={loading}
              className="w-full md:w-auto px-6 py-2 bg-green-500 text-black font-bold rounded-md hover:bg-green-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-black disabled:opacity-50 flex items-center justify-center gap-2 transition-all duration-200 font-mono"
            >
              {loading ? (
                <div className="animate-spin rounded-full h-5 w-5 border-2 border-black border-t-transparent" />
              ) : (
                <Search className="w-5 h-5" />
              )}
              执行查询
            </button>
          </form>
        </div>

        {error && (
          <div className="bg-red-900/30 border border-red-500/30 rounded-lg p-4 mb-6 flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <p className="text-red-400 font-mono">[错误] {error}</p>
          </div>
        )}

        {records.length > 0 && (
          <div className="bg-gray-900 rounded-lg border border-green-500/30 overflow-hidden backdrop-blur-sm">
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-green-500/20">
                <thead className="bg-black/50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-mono text-green-400 uppercase tracking-wider">
                      类型
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-mono text-green-400 uppercase tracking-wider">
                      名称
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-mono text-green-400 uppercase tracking-wider">
                      值
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-mono text-green-400 uppercase tracking-wider">
                      TTL
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-green-500/20">
                  {records.map((record, index) => (
                    <tr key={index} className="hover:bg-green-500/5 transition-colors">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-green-400">
                        {record.type}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-green-400">
                        {record.name}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-green-400">
                        {record.value}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-green-400">
                        {record.ttl}秒
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default App;