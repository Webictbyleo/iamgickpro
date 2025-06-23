import type { 
  TrendingTopic, 
  TopicCategory, 
  SocialAccount, 
  GeneratedContent, 
  GeneratedCreative 
} from '@/types';

// Mock Trending Topics Data
export const mockTrendingTopics: TrendingTopic[] = [
  {
    id: '1',
    title: 'AI Revolution in Workplace',
    description: 'How artificial intelligence is transforming modern workplaces and productivity',
    category: 'Technology',
    tags: ['AI', 'workplace', 'productivity', 'automation'],
    engagement: 850000,
    trending_score: 95,
    source: 'TechCrunch',
    created_at: '2025-06-23T10:00:00Z'
  },
  {
    id: '2',
    title: 'Sustainable Living Tips',
    description: 'Simple ways to reduce your carbon footprint and live more sustainably',
    category: 'Lifestyle',
    tags: ['sustainability', 'eco-friendly', 'green-living', 'environment'],
    engagement: 620000,
    trending_score: 88,
    source: 'Green Living Magazine',
    created_at: '2025-06-23T09:30:00Z'
  },
  {
    id: '3',
    title: 'Remote Work Best Practices',
    description: 'Essential strategies for successful remote work and team collaboration',
    category: 'Business',
    tags: ['remote-work', 'productivity', 'collaboration', 'work-life-balance'],
    engagement: 420000,
    trending_score: 82,
    source: 'Harvard Business Review',
    created_at: '2025-06-23T08:45:00Z'
  },
  {
    id: '4',
    title: 'Mental Health Awareness',
    description: 'Breaking stigma and promoting mental wellness in the digital age',
    category: 'Health',
    tags: ['mental-health', 'wellness', 'self-care', 'awareness'],
    engagement: 780000,
    trending_score: 91,
    source: 'Mental Health Foundation',
    created_at: '2025-06-23T11:15:00Z'
  },
  {
    id: '5',
    title: 'Cryptocurrency Market Update',
    description: 'Latest developments in blockchain technology and digital currencies',
    category: 'Finance',
    tags: ['cryptocurrency', 'blockchain', 'investment', 'fintech'],
    engagement: 590000,
    trending_score: 85,
    source: 'CoinDesk',
    created_at: '2025-06-23T12:00:00Z'
  },
  {
    id: '6',
    title: 'Travel Photography Tips',
    description: 'Capture stunning photos during your adventures with these pro tips',
    category: 'Travel',
    tags: ['photography', 'travel', 'tips', 'adventure'],
    engagement: 340000,
    trending_score: 76,
    source: 'National Geographic',
    created_at: '2025-06-23T07:20:00Z'
  }
];

// Mock Topic Categories
export const mockTopicCategories: TopicCategory[] = [
  {
    id: '1',
    name: 'Technology',
    description: 'Latest tech trends, AI, and digital innovation',
    color: 'bg-blue-500',
    icon: '🚀',
    trending_topics: mockTrendingTopics.filter(t => t.category === 'Technology')
  },
  {
    id: '2',
    name: 'Lifestyle',
    description: 'Health, wellness, and personal development',
    color: 'bg-green-500',
    icon: '🌱',
    trending_topics: mockTrendingTopics.filter(t => t.category === 'Lifestyle')
  },
  {
    id: '3',
    name: 'Business',
    description: 'Entrepreneurship, leadership, and career growth',
    color: 'bg-purple-500',
    icon: '💼',
    trending_topics: mockTrendingTopics.filter(t => t.category === 'Business')
  },
  {
    id: '4',
    name: 'Health',
    description: 'Physical and mental wellness content',
    color: 'bg-red-500',
    icon: '❤️',
    trending_topics: mockTrendingTopics.filter(t => t.category === 'Health')
  },
  {
    id: '5',
    name: 'Finance',
    description: 'Investment, crypto, and financial advice',
    color: 'bg-yellow-500',
    icon: '💰',
    trending_topics: mockTrendingTopics.filter(t => t.category === 'Finance')
  },
  {
    id: '6',
    name: 'Travel',
    description: 'Adventure, exploration, and cultural experiences',
    color: 'bg-indigo-500',
    icon: '✈️',
    trending_topics: mockTrendingTopics.filter(t => t.category === 'Travel')
  }
];

// Mock Social Accounts
export const mockSocialAccounts: SocialAccount[] = [
  {
    id: '1',
    platform: 'twitter',
    username: '@johndoe_tech',
    display_name: 'John Doe',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
    followers: 15400,
    is_connected: true,
    account_type: 'personal'
  },
  {
    id: '2',
    platform: 'linkedin',
    username: 'john-doe-developer',
    display_name: 'John Doe - Software Developer',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
    followers: 8200,
    is_connected: true,
    account_type: 'business'
  },
  {
    id: '3',
    platform: 'instagram',
    username: '@johndoe.creative',
    display_name: 'John | Creative Content',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
    followers: 22100,
    is_connected: true,
    account_type: 'creator'
  },
  {
    id: '4',
    platform: 'facebook',
    username: 'john.doe.page',
    display_name: 'John Doe Official',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
    followers: 5800,
    is_connected: false,
    account_type: 'business'
  },
  {
    id: '5',
    platform: 'tiktok',
    username: '@johntech',
    display_name: 'John Tech Tips',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
    followers: 45600,
    is_connected: true,
    account_type: 'creator'
  },
  {
    id: '6',
    platform: 'youtube',
    username: '@JohnDoeTech',
    display_name: 'John Doe Tech Channel',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
    followers: 12300,
    is_connected: true,
    account_type: 'creator'
  }
];

// Mock Generated Content
export const mockGeneratedContent: { [key: string]: GeneratedContent[] } = {
  twitter: [
    {
      id: '1',
      type: 'text',
      platforms: ['twitter'],
      primary_platform: 'twitter',
      status: 'completed',
      content: {
        text: '🚀 The AI revolution isn\'t coming - it\'s already here! From automated workflows to intelligent decision-making, AI is reshaping how we work. \n\nThe key? Learning to collaborate with AI, not compete against it. \n\n#AI #FutureOfWork #Innovation #Productivity',
        hashtags: ['AI', 'FutureOfWork', 'Innovation', 'Productivity'],
      },
      engagement_prediction: {
        likes: 245,
        shares: 52,
        comments: 18,
        reach: 8500
      },
      generated_at: '2025-06-23T13:00:00Z'
    }
  ],
  linkedin: [
    {
      id: '2',
      type: 'text',
      platforms: ['linkedin'],
      primary_platform: 'linkedin',
      status: 'completed',
      content: {
        text: 'The workplace transformation we\'re witnessing isn\'t just about technology - it\'s about human potential.\n\nAI tools are becoming our collaborative partners, handling routine tasks while we focus on creativity, strategy, and meaningful connections.\n\nKey insights from recent studies:\n• 78% of employees report increased job satisfaction when working with AI\n• Productivity gains of 30-40% in knowledge work\n• More time for strategic thinking and innovation\n\nThe future belongs to those who embrace this partnership. How is AI enhancing your work experience?\n\n#ArtificialIntelligence #FutureOfWork #ProductivityTips #WorkplaceInnovation',
        hashtags: ['ArtificialIntelligence', 'FutureOfWork', 'ProductivityTips', 'WorkplaceInnovation'],
      },
      engagement_prediction: {
        likes: 128,
        shares: 34,
        comments: 22,
        reach: 4200
      },
      generated_at: '2025-06-23T13:05:00Z'
    }
  ]
};

// Mock Generated Creatives
export const mockGeneratedCreatives: GeneratedCreative[] = [
  {
    id: '1',
    request_id: 'req_1',
    type: 'post',
    platform: 'instagram',
    image_url: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1080&h=1080&fit=crop',
    dimensions: { width: 1080, height: 1080 },
    download_url: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1080&h=1080&fit=crop&dl=ai-workplace.jpg',
    created_at: '2025-06-23T13:10:00Z'
  },
  {
    id: '2',
    request_id: 'req_2',
    type: 'thumbnail',
    platform: 'youtube',
    image_url: 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=1280&h=720&fit=crop',
    dimensions: { width: 1280, height: 720 },
    download_url: 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=1280&h=720&fit=crop&dl=ai-thumbnail.jpg',
    created_at: '2025-06-23T13:15:00Z'
  }
];

// Mock API delay simulation
export const simulateApiDelay = (ms: number = 1500): Promise<void> => {
  return new Promise(resolve => setTimeout(resolve, ms));
};
