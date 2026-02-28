export interface NotificationBreakdown {
  by_priority: {
    high: number;
    medium: number;
    low: number;
  };
  by_type: {
    [key: string]: number;
  };
}

export interface NotificationDetail {
  user_id: number;
  email: string;
  display_name: string;
  status: 'would_send' | 'sent' | 'skipped' | 'failed';
  notification_type: string;
  priority: 'high' | 'medium' | 'low';
  segment: string;
  title: string;
  text: string;
  skip_reason?: string;
  error?: string;
}

export interface BatchSendResult {
  dry_run: boolean;
  total_processed: number;
  sent: number;
  skipped: number;
  failed: number;
  breakdown: NotificationBreakdown;
  skipped_reasons: string[];
  details: NotificationDetail[];
  processing_time: string;
  summary?: {
    total_processed: number;
    sent: number;
    skipped: number;
    failed: number;
    processing_time?: string;
    by_priority?: {
      high: number;
      medium: number;
      low: number;
    };
    by_type?: {
      [key: string]: number;
    };
  };
}

export interface BatchStats {
  total_users: number;
  eligible_users: number;
  notification_types: string[];
  last_sent_at: string | null;
  total_sent_today: number;
  total_sent_this_week: number;
}

export interface BatchHistoryRecord {
  batch_time: string;
  total_processed: number;
  sent: number;
  skipped: number;
  failed: number;
  created_at: string;
  sent_at?: string;
  dry_run: boolean;
}
