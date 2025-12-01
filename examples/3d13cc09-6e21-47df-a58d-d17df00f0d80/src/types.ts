export type DNSRecord = {
  type: string;
  name: string;
  value: string;
  ttl?: number;
};