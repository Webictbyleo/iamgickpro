import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { 
  WorkflowState, 
  WorkflowStep, 
  TrendingTopic, 
  SocialAccount, 
  ContentPrompt, 
  GeneratedContent,
  CreativeRequest,
  GeneratedCreative,
  PostingBehavior,
  PostingSchedule
} from '@/types';

export const useWorkflowStore = defineStore('workflow', () => {
  // State
  const currentStep = ref<WorkflowStep>('trending');
  const completedSteps = ref<WorkflowStep[]>([]);
  const selectedTopic = ref<TrendingTopic>();
  const selectedAccounts = ref<SocialAccount[]>([]);
  const contentPrompt = ref<ContentPrompt>();
  const generatedContent = ref<GeneratedContent>();
  const creativeRequests = ref<CreativeRequest[]>([]);
  const generatedCreatives = ref<GeneratedCreative[]>([]);
  const postingBehavior = ref<PostingBehavior>();
  const postingSchedules = ref<PostingSchedule[]>([]);

  // Computed
  const workflowState = computed<WorkflowState>(() => ({
    current_step: currentStep.value,
    completed_steps: completedSteps.value,
    selected_topic: selectedTopic.value,
    selected_accounts: selectedAccounts.value,
    content_prompt: contentPrompt.value,
    generated_content: generatedContent.value,
    creative_requests: creativeRequests.value,
    generated_creatives: generatedCreatives.value,
    posting_behavior: postingBehavior.value,
    posting_schedules: postingSchedules.value,
  }));

  const progress = computed(() => {
    const steps: WorkflowStep[] = ['trending', 'platform', 'content', 'creative', 'posting'];
    return (completedSteps.value.length / steps.length) * 100;
  });

  const canProceedToNext = computed(() => {
    switch (currentStep.value) {
      case 'trending':
        return !!selectedTopic.value;
      case 'platform':
        return selectedAccounts.value.length > 0;
      case 'content':
        return !!generatedContent.value;
      case 'creative':
        return generatedCreatives.value.length > 0;
      case 'posting':
        return !!postingBehavior.value;
      default:
        return false;
    }
  });

  // Actions
  const setCurrentStep = (step: WorkflowStep) => {
    currentStep.value = step;
  };

  const completeStep = (step: WorkflowStep) => {
    if (!completedSteps.value.includes(step)) {
      completedSteps.value.push(step);
    }
  };

  const goToNextStep = () => {
    const steps: WorkflowStep[] = ['trending', 'platform', 'content', 'creative', 'posting'];
    const currentIndex = steps.indexOf(currentStep.value);
    
    if (currentIndex < steps.length - 1 && canProceedToNext.value) {
      completeStep(currentStep.value);
      setCurrentStep(steps[currentIndex + 1]);
    }
  };

  const goToPreviousStep = () => {
    const steps: WorkflowStep[] = ['trending', 'platform', 'content', 'creative', 'posting'];
    const currentIndex = steps.indexOf(currentStep.value);
    
    if (currentIndex > 0) {
      setCurrentStep(steps[currentIndex - 1]);
    }
  };

  const canNavigateTo = (step: WorkflowStep) => {
    const steps: WorkflowStep[] = ['trending', 'platform', 'content', 'creative', 'posting'];
    const stepIndex = steps.indexOf(step);
    const currentIndex = steps.indexOf(currentStep.value);
    
    // Can always navigate to the current step
    if (step === currentStep.value) return true;
    
    // Can navigate to any completed step
    if (completedSteps.value.includes(step)) return true;
    
    // Can navigate to the next step if current step is complete
    if (stepIndex === currentIndex + 1 && canProceedToNext.value) return true;
    
    // Can navigate backwards to any previous step
    if (stepIndex < currentIndex) return true;
    
    return false;
  };

  const setSelectedTopic = (topic: TrendingTopic) => {
    selectedTopic.value = topic;
  };

  const setSelectedAccount = (account: SocialAccount) => {
    selectedAccounts.value = [account];
  };

  const addSelectedAccount = (account: SocialAccount) => {
    if (!selectedAccounts.value.find(a => a.id === account.id)) {
      selectedAccounts.value.push(account);
    }
  };

  const removeSelectedAccount = (account: SocialAccount) => {
    selectedAccounts.value = selectedAccounts.value.filter(a => a.id !== account.id);
  };

  const toggleSelectedAccount = (account: SocialAccount) => {
    const isSelected = selectedAccounts.value.find(a => a.id === account.id);
    if (isSelected) {
      removeSelectedAccount(account);
    } else {
      addSelectedAccount(account);
    }
  };

  const setSelectedAccounts = (accounts: SocialAccount[]) => {
    selectedAccounts.value = accounts;
  };

  const setContentPrompt = (prompt: ContentPrompt) => {
    contentPrompt.value = prompt;
  };

  const setGeneratedContent = (content: GeneratedContent) => {
    generatedContent.value = content;
  };

  const addCreativeRequest = (request: CreativeRequest) => {
    creativeRequests.value.push(request);
  };

  const addGeneratedCreative = (creative: GeneratedCreative) => {
    generatedCreatives.value.push(creative);
  };

  const setPostingBehavior = (behavior: PostingBehavior) => {
    postingBehavior.value = behavior;
  };

  const addPostingSchedule = (schedule: PostingSchedule) => {
    postingSchedules.value.push(schedule);
  };

  const resetWorkflow = () => {
    currentStep.value = 'trending';
    completedSteps.value = [];
    selectedTopic.value = undefined;
    selectedAccounts.value = [];
    contentPrompt.value = undefined;
    generatedContent.value = undefined;
    creativeRequests.value = [];
    generatedCreatives.value = [];
    postingBehavior.value = undefined;
    postingSchedules.value = [];
  };

  return {
    // State
    currentStep,
    completedSteps,
    selectedTopic,
    selectedAccounts,
    contentPrompt,
    generatedContent,
    creativeRequests,
    generatedCreatives,
    postingBehavior,
    postingSchedules,
    
    // Computed
    workflowState,
    progress,
    canProceedToNext,
    
    // Actions
    setCurrentStep,
    completeStep,
    goToNextStep,
    goToPreviousStep,
    canNavigateTo,
    setSelectedTopic,
    setSelectedAccount,
    addSelectedAccount,
    removeSelectedAccount,
    toggleSelectedAccount,
    setSelectedAccounts,
    setContentPrompt,
    setGeneratedContent,
    addCreativeRequest,
    addGeneratedCreative,
    setPostingBehavior,
    addPostingSchedule,
    resetWorkflow,
  };
});
