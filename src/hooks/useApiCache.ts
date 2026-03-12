import * as React from 'react';

interface CacheEntry<T> {
  data: T;
  timestamp: number;
  ttl: number;
}

interface ApiCache {
  [key: string]: CacheEntry<any>;
}

const apiCache: ApiCache = {};
const DEFAULT_TTL = 5 * 60 * 1000; // 5 minutos

export function useApiCache() {
  const isExpired = (entry: CacheEntry<any>) => {
    return Date.now() - entry.timestamp > entry.ttl;
  };

  const get = <T>(key: string): T | null => {
    const entry = apiCache[key];
    if (!entry || isExpired(entry)) {
      delete apiCache[key];
      return null;
    }
    return entry.data;
  };

  const set = <T>(key: string, data: T, ttl: number = DEFAULT_TTL) => {
    apiCache[key] = {
      data,
      timestamp: Date.now(),
      ttl
    };
  };

  const invalidate = (key: string) => {
    delete apiCache[key];
  };

  const clear = () => {
    Object.keys(apiCache).forEach(key => delete apiCache[key]);
  };

  const cleanup = () => {
    Object.keys(apiCache).forEach(key => {
      if (isExpired(apiCache[key])) {
        delete apiCache[key];
      }
    });
  };

  // Limpar cache expirado a cada 10 minutos
  React.useEffect(() => {
    const interval = setInterval(cleanup, 10 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  return { get, set, invalidate, clear, cleanup };
}

// Hook para chamadas de API com cache
export function useCachedApi<T>(
  key: string,
  fetcher: () => Promise<T>,
  ttl: number = DEFAULT_TTL,
  dependencies: any[] = []
) {
  const [data, setData] = React.useState<T | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<Error | null>(null);
  const { get, set, invalidate } = useApiCache();
  const [isFetching, setIsFetching] = React.useState(false);

  const fetchData = React.useCallback(async () => {
    // Evitar múltiplas requisições simultâneas
    if (isFetching) return;
    
    setIsFetching(true);
    setLoading(true);
    setError(null);
    
    try {
      // Tentar obter do cache primeiro
      const cached = get<T>(key);
      if (cached) {
        setData(cached);
        setLoading(false);
        setIsFetching(false);
        return;
      }

      // Se não tiver cache, buscar da API
      const result = await fetcher();
      set(key, result, ttl);
      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Unknown error'));
    } finally {
      setLoading(false);
      setIsFetching(false);
    }
  }, [key, fetcher, ttl, isFetching]); // Adicionado isFetching

  React.useEffect(() => {
    fetchData();
  }, [fetchData, ...dependencies]); // dependencies já incluem mudanças relevantes

  const refetch = React.useCallback(() => {
    invalidate(key);
    fetchData();
  }, [key, invalidate, fetchData]);

  return { data, loading, error, refetch };
}
