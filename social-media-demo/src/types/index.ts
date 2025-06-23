// Social Media Platform Types
export type SocialPlatform = 'twitter' | 'facebook' | 'instagram' | 'linkedin' | 'tiktok' | 'youtube';

// Trending Topic Types
export interface TrendingTopic {
  id: string;
  title: string;
  description: string;
  category: string;
  tags: string[];
  engagement: number;
  trending_score: number;
  source: string;
  created_at: string;
}

export interface TopicCategory {
  id: string;
  name: string;
  description: string;
  color: string;
  icon: string;
  trending_topics: TrendingTopic[];
}

// Social Media Account Types
export interface SocialAccount {
  id: string;
  platform: SocialPlatform;
  username: string;
  display_name: string;
  avatar: string;
  followers: number;
  is_connected: boolean;
  account_type: 'personal' | 'business' | 'creator';
}

// Content Generation Types
export type ContentType = 'text' | 'image' | 'video' | 'carousel' | 'reel' | 'story' | 'audio' | 'mixed';
export type ContentStyle = 'informative' | 'engaging' | 'promotional' | 'storytelling' | 'tutorial' | 'behind-scenes' | 'testimonial' | 'announcement';
export type ContentTone = 'professional' | 'casual' | 'humorous' | 'inspiring' | 'educational' | 'motivational' | 'conversational' | 'authoritative';
export type ContentLength = 'short' | 'medium' | 'long' | 'micro' | 'thread';

export interface ContentPrompt {
  topic_id: string;
  platforms: SocialPlatform[];
  content_types: ContentType[];
  primary_type: ContentType;
  tone: ContentTone;
  style: ContentStyle;
  length: ContentLength;
  
  // Text-specific options
  hashtags: boolean;
  mentions: boolean;
  call_to_action: boolean;
  
  // Visual content options
  visual_style?: 'realistic' | 'artistic' | 'minimalist' | 'vibrant' | 'professional' | 'creative';
  color_scheme?: string;
  aspect_ratio?: '1:1' | '16:9' | '9:16' | '4:5' | 'custom';
  
  // Video-specific options
  video_duration?: number; // in seconds
  video_style?: 'talking-head' | 'animation' | 'slideshow' | 'cinematic' | 'casual';
  background_music?: boolean;
  
  // Audio-specific options
  voice_style?: 'professional' | 'casual' | 'energetic' | 'calm';
  audio_duration?: number; // in seconds
  
  custom_instructions?: string;
  reference_urls?: string[];
}

export interface GeneratedContent {
  id: string;
  type: ContentType;
  platforms: SocialPlatform[];
  primary_platform: SocialPlatform;
  status: 'generating' | 'completed' | 'failed' | 'processing';
  progress?: number; // 0-100 for generation progress
  
  content: {
    // Text content
    text?: string;
    caption?: string;
    hashtags?: string[];
    mentions?: string[];
    
    // Media content
    media_url?: string;
    media_urls?: string[];
    thumbnail_url?: string;
    
    // Video/Audio specific
    duration?: number;
    file_size?: number;
    
    // Metadata
    dimensions?: { width: number; height: number };
    format?: string;
  };
  
  // Generation metadata
  generation_time?: number; // in seconds
  model_used?: string;
  cost?: number;
  
  // Engagement prediction
  engagement_prediction?: {
    likes: number;
    shares: number;
    comments: number;
    reach: number;
  };
  generated_at: string;
}

// Creative Types
export type CreativeType = 'thumbnail' | 'cover' | 'story' | 'post' | 'banner' | 'logo';

export interface CreativeRequest {
  id: string;
  content_id: string;
  type: CreativeType;
  platform: SocialPlatform;
  dimensions: {
    width: number;
    height: number;
  };
  style: 'minimal' | 'bold' | 'colorful' | 'elegant' | 'modern';
  include_text: boolean;
  brand_colors?: string[];
}

export interface GeneratedCreative {
  id: string;
  request_id: string;
  type: CreativeType;
  platform: SocialPlatform;
  image_url: string;
  dimensions: {
    width: number;
    height: number;
  };
  download_url: string;
  created_at: string;
}

// Posting Behavior Types
export interface PostingSchedule {
  id: string;
  content_id: string;
  platform: SocialPlatform;
  scheduled_at: string;
  timezone: string;
  status: 'draft' | 'scheduled' | 'published' | 'failed';
  auto_optimize: boolean;
  best_time_posting: boolean;
}

export interface PostingBehavior {
  immediate: boolean;
  scheduled: boolean;
  auto_optimize: boolean;
  cross_platform: boolean;
  platforms: SocialPlatform[];
  schedule_time?: string;
  timezone: string;
}

// Workflow Types
export type WorkflowStep = 'trending' | 'platform' | 'content' | 'creative' | 'posting';

export interface WorkflowState {
  current_step: WorkflowStep;
  completed_steps: WorkflowStep[];
  selected_topic?: TrendingTopic;
  selected_accounts: SocialAccount[];
  content_prompt?: ContentPrompt;
  generated_content?: GeneratedContent;
  creative_requests?: CreativeRequest[];
  generated_creatives?: GeneratedCreative[];
  posting_behavior?: PostingBehavior;
  posting_schedules?: PostingSchedule[];
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  error?: string;
}

// Loading and Error States
export interface LoadingState {
  [key: string]: boolean;
}

export interface ErrorState {
  [key: string]: string | null;
}
