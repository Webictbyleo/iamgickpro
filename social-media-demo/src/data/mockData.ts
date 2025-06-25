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

// Mock Content Suggestions
export const mockContentSuggestions = {
  promote: [
    {
      id: '1',
      title: '🚀 Product Launch Announcement',
      preview: 'Excited to unveil our game-changing AI tool that saves 50% time...',
      engagement_score: 85,
      best_platforms: ['LinkedIn', 'Twitter', 'Instagram'],
      hashtags: ['#ProductLaunch', '#Innovation', '#AI', '#Productivity']
    },
    {
      id: '2',
      title: '💎 Feature Spotlight',
      preview: 'Here\'s why our latest feature is a game-changer for content creators...',
      engagement_score: 78,
      best_platforms: ['Instagram', 'TikTok', 'YouTube'],
      hashtags: ['#NewFeature', '#ContentCreator', '#GameChanger']
    },
    {
      id: '3',
      title: '🎯 Customer Success Story',
      preview: 'How @username increased their productivity by 200% using our platform...',
      engagement_score: 92,
      best_platforms: ['LinkedIn', 'Twitter', 'Facebook'],
      hashtags: ['#CustomerSuccess', '#Testimonial', '#Results']
    }
  ],
  educate: [
    {
      id: '4',
      title: '📚 Step-by-Step Tutorial',
      preview: 'Master AI content creation in 5 simple steps. Here\'s how to get started...',
      engagement_score: 88,
      best_platforms: ['YouTube', 'LinkedIn', 'Instagram'],
      hashtags: ['#Tutorial', '#AIContent', '#Learning', '#HowTo']
    },
    {
      id: '5',
      title: '💡 Industry Insights',
      preview: 'The future of content creation: 3 trends every creator should know...',
      engagement_score: 82,
      best_platforms: ['LinkedIn', 'Twitter', 'Medium'],
      hashtags: ['#Industry', '#Trends', '#ContentCreation', '#Future']
    },
    {
      id: '6',
      title: '🎓 Quick Tips Series',
      preview: 'Pro tip #1: The secret to viral content isn\'t what you think...',
      engagement_score: 76,
      best_platforms: ['TikTok', 'Instagram', 'Twitter'],
      hashtags: ['#ProTips', '#ContentTips', '#Viral', '#CreatorTips']
    }
  ],
  community: [
    {
      id: '7',
      title: '🗣️ Start a Discussion',
      preview: 'What\'s the biggest challenge you face as a content creator? Let\'s discuss...',
      engagement_score: 94,
      best_platforms: ['Facebook', 'LinkedIn', 'Instagram'],
      hashtags: ['#Community', '#Discussion', '#ContentCreators', '#Challenges']
    },
    {
      id: '8',
      title: '📊 Community Poll',
      preview: 'Which content format gets you the most engagement? Vote below! 👇',
      engagement_score: 89,
      best_platforms: ['Instagram', 'Twitter', 'LinkedIn'],
      hashtags: ['#Poll', '#Community', '#Engagement', '#ContentFormat']
    },
    {
      id: '9',
      title: '🎉 Behind the Scenes',
      preview: 'Take a peek behind the scenes of how we create viral content...',
      engagement_score: 87,
      best_platforms: ['Instagram', 'TikTok', 'YouTube'],
      hashtags: ['#BehindTheScenes', '#ContentCreation', '#Process', '#Authentic']
    }
  ]
};

// Mock Smart Suggestions based on trending topics and user behavior
export const mockSmartSuggestions = [
  {
    id: 'smart_1',
    type: 'trending',
    title: 'Trending: AI Workplace Revolution',
    reason: 'This topic is gaining 300% more engagement this week',
    confidence: 95,
    estimated_reach: '15K+',
    suggested_content: 'Share your take on how AI is changing your workplace',
    best_time: '2:00 PM - 4:00 PM',
    platforms: ['LinkedIn', 'Twitter']
  },
  {
    id: 'smart_2',
    type: 'audience',
    title: 'Your Audience Loves Educational Content',
    reason: 'Your educational posts get 240% more engagement',
    confidence: 88,
    estimated_reach: '8K+',
    suggested_content: 'Create a quick tutorial or how-to guide',
    best_time: '10:00 AM - 12:00 PM',
    platforms: ['YouTube', 'Instagram', 'LinkedIn']
  },
  {
    id: 'smart_3',
    type: 'timing',
    title: 'Perfect Timing for Video Content',
    reason: 'Video posts perform 180% better in the next 2 hours',
    confidence: 76,
    estimated_reach: '12K+',
    suggested_content: 'Record a short video sharing quick insights',
    best_time: 'Next 2 hours',
    platforms: ['TikTok', 'Instagram', 'YouTube']
  }
];

// Mock engagement predictions
export const mockEngagementPredictions = {
  text: {
    likes: { min: 50, max: 300, avg: 150 },
    shares: { min: 10, max: 80, avg: 35 },
    comments: { min: 5, max: 50, avg: 20 },
    reach: { min: 1000, max: 5000, avg: 2500 }
  },
  image: {
    likes: { min: 80, max: 500, avg: 250 },
    shares: { min: 15, max: 120, avg: 60 },
    comments: { min: 8, max: 80, avg: 35 },
    reach: { min: 2000, max: 8000, avg: 4200 }
  },
  video: {
    likes: { min: 150, max: 1000, avg: 450 },
    shares: { min: 30, max: 200, avg: 100 },
    comments: { min: 20, max: 150, avg: 70 },
    reach: { min: 3000, max: 15000, avg: 8700 }
  }
};

// Mock content templates by goal
export const mockContentTemplates = {
  promote: {
    hooks: [
      "🚀 Excited to share something game-changing...",
      "💎 This is why [product] is different...",
      "🎯 The solution you've been waiting for...",
      "✨ Ready to transform your [industry]?",
      "🔥 Join thousands who are already using..."
    ],
    structures: [
      "Hook → Problem → Solution → Benefit → CTA",
      "Story → Result → Product → Social Proof → CTA",
      "Question → Pain Point → Solution → Outcome → CTA"
    ]
  },
  educate: {
    hooks: [
      "📚 Here's what I learned after [experience]...",
      "💡 The secret to [topic] that nobody talks about...",
      "🎓 Want to master [skill]? Here's how...",
      "📖 The complete guide to [subject]...",
      "🧠 Mind-blown: This will change how you think about..."
    ],
    structures: [
      "Hook → Context → Steps → Example → Takeaway",
      "Question → Problem → Solution → Explanation → Action",
      "Fact → Why It Matters → How To Apply → Results"
    ]
  },
  community: {
    hooks: [
      "🗣️ Let's settle this debate once and for all...",
      "🤔 Unpopular opinion: [controversial statement]...",
      "💭 What's your take on [trending topic]?",
      "🎯 Quick question for my community...",
      "🔥 Hot take: [opinion] - agree or disagree?"
    ],
    structures: [
      "Hook → Opinion → Reasoning → Question → Engagement",
      "Question → Context → Your View → Call for Responses",
      "Statement → Supporting Points → Counter Arguments → Discussion"
    ]
  }
};

// Enhanced Mock Content Suggestions with more variety
export const enhancedMockContentSuggestions = {
  promote: [
    {
      id: '1',
      title: '🚀 Product Launch Announcement',
      preview: 'Excited to unveil our game-changing AI tool that saves 50% time...',
      engagement_score: 85,
      best_platforms: ['LinkedIn', 'Twitter', 'Instagram'],
      hashtags: ['#ProductLaunch', '#Innovation', '#AI', '#Productivity'],
      template: 'announcement',
      estimated_reach: '15K+',
      virality_potential: 78
    },
    {
      id: '2',
      title: '💎 Feature Spotlight',
      preview: 'Here\'s why our latest feature is a game-changer for content creators...',
      engagement_score: 78,
      best_platforms: ['Instagram', 'TikTok', 'YouTube'],
      hashtags: ['#NewFeature', '#ContentCreator', '#GameChanger'],
      template: 'feature_demo',
      estimated_reach: '8K+',
      virality_potential: 65
    },
    {
      id: '3',
      title: '🎯 Customer Success Story',
      preview: 'How @username increased their productivity by 200% using our platform...',
      engagement_score: 92,
      best_platforms: ['LinkedIn', 'Twitter', 'Facebook'],
      hashtags: ['#CustomerSuccess', '#Testimonial', '#Results'],
      template: 'testimonial',
      estimated_reach: '12K+',
      virality_potential: 82
    },
    {
      id: '4',
      title: '🔥 Limited Time Offer',
      preview: 'Only 48 hours left! Get 50% off our premium features...',
      engagement_score: 88,
      best_platforms: ['Instagram', 'Facebook', 'Twitter'],
      hashtags: ['#LimitedOffer', '#Sale', '#LastChance'],
      template: 'urgency',
      estimated_reach: '20K+',
      virality_potential: 90
    }
  ],
  educate: [
    {
      id: '5',
      title: '📚 Step-by-Step Tutorial',
      preview: 'Master AI content creation in 5 simple steps. Here\'s how to get started...',
      engagement_score: 88,
      best_platforms: ['YouTube', 'LinkedIn', 'Instagram'],
      hashtags: ['#Tutorial', '#AIContent', '#Learning', '#HowTo'],
      template: 'tutorial',
      estimated_reach: '10K+',
      virality_potential: 75
    },
    {
      id: '6',
      title: '💡 Industry Insights',
      preview: 'The future of content creation: 3 trends every creator should know...',
      engagement_score: 82,
      best_platforms: ['LinkedIn', 'Twitter', 'Medium'],
      hashtags: ['#Industry', '#Trends', '#ContentCreation', '#Future'],
      template: 'insights',
      estimated_reach: '7K+',
      virality_potential: 68
    },
    {
      id: '7',
      title: '🎓 Quick Tips Series',
      preview: 'Pro tip #1: The secret to viral content isn\'t what you think...',
      engagement_score: 76,
      best_platforms: ['TikTok', 'Instagram', 'Twitter'],
      hashtags: ['#ProTips', '#ContentTips', '#Viral', '#CreatorTips'],
      template: 'quick_tips',
      estimated_reach: '5K+',
      virality_potential: 85
    },
    {
      id: '8',
      title: '🧠 Myth Busting',
      preview: 'Debunking 5 common myths about AI in content creation...',
      engagement_score: 84,
      best_platforms: ['LinkedIn', 'YouTube', 'Twitter'],
      hashtags: ['#MythBusting', '#AI', '#Facts', '#Truth'],
      template: 'myth_busting',
      estimated_reach: '9K+',
      virality_potential: 72
    }
  ],
  community: [
    {
      id: '9',
      title: '🗣️ Start a Discussion',
      preview: 'What\'s the biggest challenge you face as a content creator? Let\'s discuss...',
      engagement_score: 94,
      best_platforms: ['Facebook', 'LinkedIn', 'Instagram'],
      hashtags: ['#Community', '#Discussion', '#ContentCreators', '#Challenges'],
      template: 'discussion_starter',
      estimated_reach: '6K+',
      virality_potential: 88
    },
    {
      id: '10',
      title: '📊 Community Poll',
      preview: 'Which content format gets you the most engagement? Vote below! 👇',
      engagement_score: 89,
      best_platforms: ['Instagram', 'Twitter', 'LinkedIn'],
      hashtags: ['#Poll', '#Community', '#Engagement', '#ContentFormat'],
      template: 'poll',
      estimated_reach: '8K+',
      virality_potential: 92
    },
    {
      id: '11',
      title: '🎉 Behind the Scenes',
      preview: 'Take a peek behind the scenes of how we create viral content...',
      engagement_score: 87,
      best_platforms: ['Instagram', 'TikTok', 'YouTube'],
      hashtags: ['#BehindTheScenes', '#ContentCreation', '#Process', '#Authentic'],
      template: 'behind_scenes',
      estimated_reach: '11K+',
      virality_potential: 80
    },
    {
      id: '12',
      title: '🤝 Collaboration Call',
      preview: 'Looking for content creators to collaborate with. Drop your best work below!',
      engagement_score: 91,
      best_platforms: ['Instagram', 'TikTok', 'Twitter'],
      hashtags: ['#Collaboration', '#ContentCreators', '#Partnership', '#Community'],
      template: 'collaboration',
      estimated_reach: '13K+',
      virality_potential: 87
    }
  ],
  news: [
    {
      id: '13',
      title: '📢 Industry Breaking News',
      preview: 'BREAKING: Major platform announces new creator monetization features...',
      engagement_score: 95,
      best_platforms: ['Twitter', 'LinkedIn', 'Facebook'],
      hashtags: ['#Breaking', '#Industry', '#CreatorEconomy', '#News'],
      template: 'breaking_news',
      estimated_reach: '25K+',
      virality_potential: 95
    },
    {
      id: '14',
      title: '🎯 Weekly Roundup',
      preview: 'This week in content creation: AI tools, platform updates, and trending topics...',
      engagement_score: 79,
      best_platforms: ['LinkedIn', 'Newsletter', 'Blog'],
      hashtags: ['#WeeklyRoundup', '#ContentCreation', '#Updates'],
      template: 'roundup',
      estimated_reach: '4K+',
      virality_potential: 45
    },
    {
      id: '15',
      title: '🔮 Future Predictions',
      preview: 'What content creation will look like in 2026: 5 bold predictions...',
      engagement_score: 86,
      best_platforms: ['LinkedIn', 'YouTube', 'Medium'],
      hashtags: ['#Predictions', '#Future', '#ContentCreation', '#2026'],
      template: 'predictions',
      estimated_reach: '12K+',
      virality_potential: 73
    }
  ]
};

// Enhanced Smart Suggestions with more intelligence
export const enhancedSmartSuggestions = [
  {
    id: 'smart_1',
    type: 'trending',
    title: 'AI Workplace Revolution is Trending',
    reason: 'This topic is gaining 300% more engagement this week',
    confidence: 95,
    estimated_reach: '15K+',
    suggested_content: 'Share your take on how AI is changing your workplace',
    best_time: '2:00 PM - 4:00 PM',
    platforms: ['LinkedIn', 'Twitter'],
    urgency: 'high',
    trend_score: 95,
    competition_level: 'medium'
  },
  {
    id: 'smart_2',
    type: 'audience',
    title: 'Your Audience Loves Educational Content',
    reason: 'Your educational posts get 240% more engagement',
    confidence: 88,
    estimated_reach: '8K+',
    suggested_content: 'Create a quick tutorial or how-to guide',
    best_time: '10:00 AM - 12:00 PM',
    platforms: ['YouTube', 'Instagram', 'LinkedIn'],
    urgency: 'medium',
    trend_score: 70,
    competition_level: 'low'
  },
  {
    id: 'smart_3',
    type: 'timing',
    title: 'Perfect Timing for Video Content',
    reason: 'Video posts perform 180% better in the next 2 hours',
    confidence: 76,
    estimated_reach: '12K+',
    suggested_content: 'Record a short video sharing quick insights',
    best_time: 'Next 2 hours',
    platforms: ['TikTok', 'Instagram', 'YouTube'],
    urgency: 'urgent',
    trend_score: 85,
    competition_level: 'high'
  },
  {
    id: 'smart_4',
    type: 'viral_opportunity',
    title: 'Viral Meme Format Detected',
    reason: 'This meme format has 500% higher viral potential',
    confidence: 92,
    estimated_reach: '50K+',
    suggested_content: 'Adapt this trending meme to your niche',
    best_time: 'ASAP',
    platforms: ['TikTok', 'Instagram', 'Twitter'],
    urgency: 'urgent',
    trend_score: 98,
    competition_level: 'very_high'
  },
  {
    id: 'smart_5',
    type: 'seasonal',
    title: 'Holiday Content Opportunity',
    reason: 'Holiday-themed content gets 45% more shares',
    confidence: 81,
    estimated_reach: '18K+',
    suggested_content: 'Create holiday-themed content in your niche',
    best_time: '6:00 PM - 8:00 PM',
    platforms: ['Instagram', 'Facebook', 'TikTok'],
    urgency: 'medium',
    trend_score: 75,
    competition_level: 'medium'
  },
  {
    id: 'smart_6',
    type: 'collaboration',
    title: 'High-Engagement Creator Available',
    reason: 'Similar creator wants to collaborate - 300% engagement boost potential',
    confidence: 89,
    estimated_reach: '35K+',
    suggested_content: 'Reach out for a collaboration post',
    best_time: 'This week',
    platforms: ['Instagram', 'TikTok', 'YouTube'],
    urgency: 'high',
    trend_score: 88,
    competition_level: 'low'
  }
];

// Enhanced engagement predictions with more detailed metrics
export const enhancedEngagementPredictions = {
  text: {
    likes: { min: 50, max: 300, avg: 150, growth_rate: 1.2 },
    shares: { min: 10, max: 80, avg: 35, growth_rate: 1.5 },
    comments: { min: 5, max: 50, avg: 20, growth_rate: 2.1 },
    reach: { min: 1000, max: 5000, avg: 2500, growth_rate: 1.1 },
    saves: { min: 20, max: 150, avg: 65, growth_rate: 1.8 },
    click_through_rate: '2.3%',
    best_posting_times: ['9:00 AM', '1:00 PM', '7:00 PM'],
    peak_engagement_day: 'Tuesday'
  },
  image: {
    likes: { min: 80, max: 500, avg: 250, growth_rate: 1.4 },
    shares: { min: 15, max: 120, avg: 60, growth_rate: 1.7 },
    comments: { min: 8, max: 80, avg: 35, growth_rate: 2.3 },
    reach: { min: 2000, max: 8000, avg: 4200, growth_rate: 1.3 },
    saves: { min: 40, max: 300, avg: 125, growth_rate: 2.2 },
    click_through_rate: '3.1%',
    best_posting_times: ['11:00 AM', '3:00 PM', '8:00 PM'],
    peak_engagement_day: 'Wednesday'
  },
  video: {
    likes: { min: 150, max: 1000, avg: 450, growth_rate: 1.8 },
    shares: { min: 30, max: 200, avg: 100, growth_rate: 2.1 },
    comments: { min: 20, max: 150, avg: 70, growth_rate: 2.8 },
    reach: { min: 3000, max: 15000, avg: 8700, growth_rate: 1.6 },
    saves: { min: 60, max: 400, avg: 180, growth_rate: 2.5 },
    click_through_rate: '4.7%',
    best_posting_times: ['12:00 PM', '6:00 PM', '9:00 PM'],
    peak_engagement_day: 'Friday'
  },
  carousel: {
    likes: { min: 100, max: 700, avg: 320, growth_rate: 1.6 },
    shares: { min: 25, max: 140, avg: 75, growth_rate: 1.9 },
    comments: { min: 15, max: 100, avg: 50, growth_rate: 2.5 },
    reach: { min: 2500, max: 10000, avg: 5800, growth_rate: 1.4 },
    saves: { min: 80, max: 500, avg: 220, growth_rate: 2.8 },
    click_through_rate: '3.8%',
    best_posting_times: ['10:00 AM', '2:00 PM', '7:30 PM'],
    peak_engagement_day: 'Thursday'
  }
};

// Enhanced content templates with more structure and hooks
export const enhancedContentTemplates = {
  promote: {
    hooks: [
      "🚀 Game-changer alert! Here's what's new...",
      "💎 This is why [product] is different from everything else...",
      "🎯 The solution you didn't know you needed...",
      "✨ Ready to transform your [industry] game?",
      "🔥 Join 10,000+ who are already using this...",
      "💪 Stop struggling with [problem]. Here's the fix...",
      "🌟 What if I told you there's a better way to [action]?",
      "🎉 Finally! A [product] that actually works..."
    ],
    structures: [
      "Hook → Problem → Solution → Benefit → Social Proof → CTA",
      "Story → Struggle → Discovery → Result → Product → CTA",
      "Question → Pain Point → Solution → Outcome → Testimonial → CTA",
      "Statistic → Problem → Product → Features → Guarantee → CTA",
      "Before/After → Product → How It Works → Benefits → Limited Time → CTA"
    ],
    call_to_actions: [
      "Link in bio to get started →",
      "DM me 'INTERESTED' for early access",
      "Comment 'ME' to learn more",
      "Save this post and share with a friend",
      "What questions do you have? Ask below! 👇"
    ]
  },
  educate: {
    hooks: [
      "📚 Here's what I learned after [timeframe] of [experience]...",
      "💡 The secret to [topic] that nobody talks about...",
      "🎓 Want to master [skill]? Here's the roadmap...",
      "📖 The complete guide to [subject] (save this!)...",
      "🧠 Mind-blown: This will change how you think about [topic]...",
      "🔍 I analyzed 100+ [examples] and found this pattern...",
      "⚡ Quick lesson: Why [common belief] is wrong...",
      "🎯 The #1 mistake people make with [topic] (and how to fix it)..."
    ],
    structures: [
      "Hook → Context → Step 1 → Step 2 → Step 3 → Key Takeaway → Action",
      "Question → Problem → Explanation → Example → Application → Results → Next Steps",
      "Fact → Why It Matters → How To Apply → Common Mistakes → Pro Tips → Summary",
      "Story → Lesson → Method → Implementation → Results → Takeaway → CTA",
      "Myth → Truth → Evidence → Application → Benefits → Action → Resources"
    ],
    call_to_actions: [
      "What other topics should I cover?",
      "Save this for your next project",
      "Tag someone who needs to see this",
      "Share your own tips in the comments",
      "What's your biggest challenge with this?"
    ]
  },
  community: {
    hooks: [
      "🗣️ Let's settle this debate once and for all...",
      "🤔 Unpopular opinion: [controversial statement]...",
      "💭 What's your honest take on [trending topic]?",
      "🎯 Quick question for my community...",
      "🔥 Hot take: [opinion] - agree or disagree?",
      "👀 Am I the only one who thinks [opinion]?",
      "💬 Real talk: [vulnerable statement]...",
      "🤷‍♀️ Maybe it's just me, but [observation]..."
    ],
    structures: [
      "Hook → Opinion → Reasoning → Question → Call for Responses",
      "Question → Context → Your View → Alternative Views → Discussion Starter",
      "Statement → Supporting Points → Counter Arguments → Community Question",
      "Personal Story → Lesson → Question → Encourage Sharing",
      "Observation → Why It Matters → Different Perspectives → Ask for Input"
    ],
    call_to_actions: [
      "Drop your thoughts below! 👇",
      "Who else feels this way?",
      "Change my mind in the comments",
      "Share this if you agree",
      "Tag someone who needs to join this conversation"
    ]
  },
  news: {
    hooks: [
      "📢 BREAKING: [Industry] just changed forever...",
      "🚨 This news will impact every [target audience]...",
      "⚡ UPDATE: Major announcement from [company/industry]...",
      "🔥 Hot off the press: [news summary]...",
      "📰 In case you missed it: [important update]...",
      "🎯 Why this news matters to you...",
      "💥 Plot twist: [unexpected development]...",
      "📊 New data reveals [surprising finding]..."
    ],
    structures: [
      "Breaking News → What Happened → Why It Matters → Impact → What's Next",
      "Headline → Background → Key Details → Analysis → Implications → Action",
      "Update → Context → New Information → Significance → Future Outlook",
      "Announcement → Details → Industry Impact → Your Impact → Response",
      "Data/Study → Findings → What Changed → Why Now → What To Do"
    ],
    call_to_actions: [
      "What's your take on this development?",
      "How will this affect your [business/work]?",
      "Are you ready for these changes?",
      "Share this with your network",
      "What questions do you have about this?"
    ]
  }
};

// Goal-specific success metrics and benchmarks
export const goalSuccessMetrics = {
  promote: {
    primary_kpi: 'conversions',
    engagement_benchmark: '5.2%',
    ideal_content_mix: ['product_demo', 'testimonial', 'feature_highlight'],
    optimal_posting_frequency: '3-4 times per week',
    best_content_length: 'medium',
    top_performing_formats: ['video', 'carousel', 'image'],
    success_indicators: [
      'High click-through rates',
      'Increased website traffic',
      'Lead generation',
      'Sales conversions'
    ]
  },
  educate: {
    primary_kpi: 'saves_and_shares',
    engagement_benchmark: '7.8%',
    ideal_content_mix: ['tutorial', 'tips', 'insights', 'case_study'],
    optimal_posting_frequency: '2-3 times per week',
    best_content_length: 'long',
    top_performing_formats: ['carousel', 'video', 'text'],
    success_indicators: [
      'High save rates',
      'Increased followers',
      'Quality comments',
      'Share velocity'
    ]
  },
  community: {
    primary_kpi: 'comments_and_engagement',
    engagement_benchmark: '12.5%',
    ideal_content_mix: ['question', 'poll', 'discussion', 'behind_scenes'],
    optimal_posting_frequency: '5-7 times per week',
    best_content_length: 'short_to_medium',
    top_performing_formats: ['text', 'image', 'video'],
    success_indicators: [
      'High comment rates',
      'Community growth',
      'User-generated content',
      'Brand loyalty'
    ]
  },
  news: {
    primary_kpi: 'reach_and_impressions',
    engagement_benchmark: '4.1%',
    ideal_content_mix: ['breaking_news', 'analysis', 'roundup', 'prediction'],
    optimal_posting_frequency: '1-2 times per day',
    best_content_length: 'short',
    top_performing_formats: ['text', 'image', 'video'],
    success_indicators: [
      'High reach',
      'Fast engagement',
      'Media mentions',
      'Thought leadership'
    ]
  }
};

// Platform-specific optimization tips
export const platformOptimizationTips = {
  twitter: {
    character_limit: 280,
    optimal_length: '100-140 characters',
    best_hashtags: '1-2',
    ideal_posting_times: ['9:00 AM', '12:00 PM', '3:00 PM'],
    engagement_tactics: ['Threading', 'Polls', 'Replies', 'Retweets'],
    content_tips: [
      'Use conversational tone',
      'Include trending hashtags',
      'Engage with replies quickly',
      'Share quick insights and hot takes'
    ]
  },
  instagram: {
    character_limit: 2200,
    optimal_length: '125-150 characters for caption',
    best_hashtags: '5-10 relevant',
    ideal_posting_times: ['11:00 AM', '2:00 PM', '5:00 PM'],
    engagement_tactics: ['Stories', 'Reels', 'IGTV', 'Live'],
    content_tips: [
      'High-quality visuals essential',
      'Use Instagram-specific hashtags',
      'Encourage saves and shares',
      'Post consistently to Stories'
    ]
  },
  linkedin: {
    character_limit: 3000,
    optimal_length: '150-300 words',
    best_hashtags: '3-5 professional',
    ideal_posting_times: ['8:00 AM', '12:00 PM', '5:00 PM'],
    engagement_tactics: ['Articles', 'Polls', 'Documents', 'Videos'],
    content_tips: [
      'Professional tone and insights',
      'Include industry expertise',
      'Network and comment actively',
      'Share valuable business content'
    ]
  },
  facebook: {
    character_limit: 63206,
    optimal_length: '40-80 characters',
    best_hashtags: '1-3',
    ideal_posting_times: ['1:00 PM', '3:00 PM', '9:00 PM'],
    engagement_tactics: ['Groups', 'Events', 'Live Video', 'Stories'],
    content_tips: [
      'Community-focused content',
      'Use Facebook Groups',
      'Encourage discussion',
      'Share behind-the-scenes content'
    ]
  },
  tiktok: {
    character_limit: 2200,
    optimal_length: '21-34 characters',
    best_hashtags: '3-5 trending',
    ideal_posting_times: ['6:00 AM', '10:00 AM', '7:00 PM'],
    engagement_tactics: ['Trends', 'Sounds', 'Effects', 'Challenges'],
    content_tips: [
      'Jump on trending sounds',
      'Quick, engaging content',
      'Use popular effects',
      'Participate in challenges'
    ]
  },
  youtube: {
    character_limit: 5000,
    optimal_length: '125-175 characters',
    best_hashtags: '10-15',
    ideal_posting_times: ['2:00 PM', '6:00 PM', '9:00 PM'],
    engagement_tactics: ['Thumbnails', 'End Screens', 'Cards', 'Community'],
    content_tips: [
      'Compelling thumbnails crucial',
      'Strong opening hooks',
      'Clear call-to-actions',
      'Consistent upload schedule'
    ]
  }
};
