import type { 
  ApiResponse, 
  TrendingTopic, 
  TopicCategory, 
  SocialAccount, 
  ContentPrompt, 
  GeneratedContent,
  CreativeRequest,
  GeneratedCreative,
  PostingBehavior,
  PostingSchedule,
  SocialPlatform
} from '@/types';

import { 
  mockTrendingTopics, 
  mockTopicCategories, 
  mockSocialAccounts, 
  mockGeneratedContent, 
  mockGeneratedCreatives,
  simulateApiDelay 
} from '@/data/mockData';

// Trending Topics API
export const trendingApi = {
  async getCategories(): Promise<ApiResponse<TopicCategory[]>> {
    await simulateApiDelay(800);
    return {
      success: true,
      data: mockTopicCategories,
      message: 'Categories fetched successfully'
    };
  },

  async getTrendingTopics(categoryId?: string): Promise<ApiResponse<TrendingTopic[]>> {
    await simulateApiDelay(1000);
    
    let topics = mockTrendingTopics;
    if (categoryId) {
      const category = mockTopicCategories.find(c => c.id === categoryId);
      topics = category?.trending_topics || [];
    }
    
    return {
      success: true,
      data: topics,
      message: 'Trending topics fetched successfully'
    };
  },

  async searchTopics(query: string): Promise<ApiResponse<TrendingTopic[]>> {
    await simulateApiDelay(600);
    
    const filteredTopics = mockTrendingTopics.filter(topic =>
      topic.title.toLowerCase().includes(query.toLowerCase()) ||
      topic.description.toLowerCase().includes(query.toLowerCase()) ||
      topic.tags.some(tag => tag.toLowerCase().includes(query.toLowerCase()))
    );
    
    return {
      success: true,
      data: filteredTopics,
      message: `Found ${filteredTopics.length} topics matching "${query}"`
    };
  }
};

// Social Accounts API
export const socialAccountsApi = {
  async getAccounts(): Promise<ApiResponse<SocialAccount[]>> {
    await simulateApiDelay(600);
    return {
      success: true,
      data: mockSocialAccounts,
      message: 'Social accounts fetched successfully'
    };
  },

  async getAccountsByPlatform(platform: SocialPlatform): Promise<ApiResponse<SocialAccount[]>> {
    await simulateApiDelay(400);
    
    const accounts = mockSocialAccounts.filter(account => account.platform === platform);
    
    return {
      success: true,
      data: accounts,
      message: `${platform} accounts fetched successfully`
    };
  },

  async connectAccount(platform: SocialPlatform): Promise<ApiResponse<{ success: boolean }>> {
    await simulateApiDelay(2000);
    
    // Simulate OAuth flow
    return {
      success: true,
      data: { success: true },
      message: `Successfully connected to ${platform}`
    };
  }
};

// Content Generation API
export const contentApi = {
  async generateContent(prompt: ContentPrompt): Promise<ApiResponse<GeneratedContent>> {
    await simulateApiDelay(2500);
    
    // Get mock content for the primary platform
    const primaryPlatform = prompt.platforms[0];
    const platformContent = mockGeneratedContent[primaryPlatform];
    if (!platformContent || platformContent.length === 0) {
      // Generate default content
      const content: GeneratedContent = {
        id: Date.now().toString(),
        type: prompt.primary_type,
        platforms: prompt.platforms,
        primary_platform: primaryPlatform,
        status: 'completed',
        content: {
          text: `🚀 Exciting insights about "${mockTrendingTopics.find(t => t.id === prompt.topic_id)?.title}" that you need to know!\n\nThis trending topic is gaining massive traction across social media. Here's why it matters and how you can leverage it for your audience.\n\n${prompt.hashtags ? '#TrendingNow #SocialMedia #ContentCreation' : ''}`,
          hashtags: prompt.hashtags ? ['TrendingNow', 'SocialMedia', 'ContentCreation'] : [],
        },
        engagement_prediction: {
          likes: Math.floor(Math.random() * 500) + 100,
          shares: Math.floor(Math.random() * 100) + 20,
          comments: Math.floor(Math.random() * 50) + 10,
          reach: Math.floor(Math.random() * 10000) + 2000
        },
        generated_at: new Date().toISOString()
      };
      
      return {
        success: true,
        data: content,
        message: 'Content generated successfully'
      };
    }
    
    return {
      success: true,
      data: platformContent[0],
      message: 'Content generated successfully'
    };
  },

  async regenerateContent(_contentId: string, prompt: ContentPrompt): Promise<ApiResponse<GeneratedContent>> {
    await simulateApiDelay(2000);
    
    const content: GeneratedContent = {
      id: Date.now().toString(),
      type: prompt.primary_type,
      platforms: prompt.platforms,
      primary_platform: prompt.platforms[0],
      status: 'completed',
      content: {
        text: `✨ Fresh take on "${mockTrendingTopics.find(t => t.id === prompt.topic_id)?.title}"!\n\nHere's a different perspective that will resonate with your audience. This approach focuses on practical insights and actionable takeaways.\n\n${prompt.hashtags ? '#Innovation #Insights #Growth' : ''}`,
        hashtags: prompt.hashtags ? ['Innovation', 'Insights', 'Growth'] : [],
      },
      engagement_prediction: {
        likes: Math.floor(Math.random() * 600) + 150,
        shares: Math.floor(Math.random() * 120) + 30,
        comments: Math.floor(Math.random() * 60) + 15,
        reach: Math.floor(Math.random() * 12000) + 3000
      },
      generated_at: new Date().toISOString()
    };
    
    return {
      success: true,
      data: content,
      message: 'Content regenerated successfully'
    };
  }
};

// Creative Generation API
export const creativeApi = {
  async generateCreative(request: CreativeRequest): Promise<ApiResponse<GeneratedCreative>> {
    await simulateApiDelay(3000);
    
    const creative: GeneratedCreative = {
      id: Date.now().toString(),
      request_id: request.id,
      type: request.type,
      platform: request.platform,
      image_url: mockGeneratedCreatives[0]?.image_url || 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1080&h=1080&fit=crop',
      dimensions: request.dimensions,
      download_url: `https://images.unsplash.com/photo-1677442136019-21780ecad995?w=${request.dimensions.width}&h=${request.dimensions.height}&fit=crop&dl=generated-creative.jpg`,
      created_at: new Date().toISOString()
    };
    
    return {
      success: true,
      data: creative,
      message: 'Creative generated successfully'
    };
  },

  async generateMultipleCreatives(requests: CreativeRequest[]): Promise<ApiResponse<GeneratedCreative[]>> {
    await simulateApiDelay(4000);
    
    const creatives = requests.map((request, index) => ({
      id: (Date.now() + index).toString(),
      request_id: request.id,
      type: request.type,
      platform: request.platform,
      image_url: mockGeneratedCreatives[index % mockGeneratedCreatives.length]?.image_url || 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1080&h=1080&fit=crop',
      dimensions: request.dimensions,
      download_url: `https://images.unsplash.com/photo-1677442136019-21780ecad995?w=${request.dimensions.width}&h=${request.dimensions.height}&fit=crop&dl=generated-creative-${index}.jpg`,
      created_at: new Date().toISOString()
    }));
    
    return {
      success: true,
      data: creatives,
      message: `Generated ${creatives.length} creatives successfully`
    };
  }
};

// Posting API
export const postingApi = {
  async schedulePost(behavior: PostingBehavior, contentId: string): Promise<ApiResponse<PostingSchedule[]>> {
    await simulateApiDelay(1500);
    
    const schedules: PostingSchedule[] = behavior.platforms.map(platform => ({
      id: Date.now().toString() + platform,
      content_id: contentId,
      platform,
      scheduled_at: behavior.schedule_time || new Date().toISOString(),
      timezone: behavior.timezone,
      status: behavior.immediate ? 'published' : 'scheduled',
      auto_optimize: behavior.auto_optimize,
      best_time_posting: behavior.auto_optimize
    }));
    
    return {
      success: true,
      data: schedules,
      message: behavior.immediate ? 'Content published successfully' : 'Content scheduled successfully'
    };
  },

  async publishImmediately(_contentId: string, platforms: SocialPlatform[]): Promise<ApiResponse<{ published: SocialPlatform[], failed: SocialPlatform[] }>> {
    await simulateApiDelay(2000);
    
    // Simulate some failures for demo purposes
    const published = platforms.slice(0, Math.max(1, platforms.length - 1));
    const failed = platforms.slice(published.length);
    
    return {
      success: true,
      data: { published, failed },
      message: `Published to ${published.length} platforms${failed.length > 0 ? `, failed on ${failed.length}` : ''}`
    };
  }
};
