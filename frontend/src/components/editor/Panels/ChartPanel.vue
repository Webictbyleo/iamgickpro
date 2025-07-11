<template>
  <div class="p-4">
    <!-- Chart Type Selection -->
    <div class="mb-4">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Chart Type</h3>
      
      <!-- Chart type conversion warning -->
      <div class="mb-3 p-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded text-xs text-amber-700 dark:text-amber-300">
        ⚠️ <strong>Note:</strong> Changing chart type will automatically convert your data structure. Scatter/bubble charts use coordinates, while pie charts use individual slice colors.
      </div>
      
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="type in chartTypes"
          :key="type.value"
          @click="updateProperty('chartType', type.value)"
          :class="[
            'px-2 py-2 flex flex-col items-center space-y-1 transition-all duration-200 rounded-md border text-xs',
            chartType === type.value
              ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700'
              : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-blue-400 dark:hover:bg-gray-700 border-gray-200 dark:border-gray-600'
          ]"
        >
          <component :is="type.icon" class="w-4 h-4" />
          <span>{{ type.label }}</span>
        </button>
      </div>
    </div>

    <!-- Chart Data -->
    <div class="mb-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Chart Data</h3>
        <div class="flex items-center space-x-1">
          <button
            @click="addDataset"
            class="px-2 py-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 border border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 rounded transition-colors"
          >
            <PlusIcon class="w-3 h-3 mr-1 inline" />
            Dataset
          </button>
          <button
            @click="addRow"
            class="px-2 py-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 border border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 rounded transition-colors"
          >
            <PlusIcon class="w-3 h-3 mr-1 inline" />
            Row
          </button>
        </div>
      </div>
      
      <!-- Data format hint for scatter/bubble charts -->
      <div v-if="isScatterOrBubbleChart" class="mb-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-xs text-blue-700 dark:text-blue-300">
        <span v-if="isBubbleChart">💡 Double-click cells to edit. Use Tab/Enter to navigate. Bubble charts use x,y,r coordinates (x=horizontal, y=vertical, r=radius)</span>
        <span v-else>💡 Double-click cells to edit. Use Tab/Enter to navigate. Scatter charts use x,y coordinates (x=horizontal, y=vertical)</span>
      </div>
      <div v-else-if="isPieOrDoughnutChart" class="mb-2 p-2 bg-purple-50 dark:bg-purple-900/20 rounded text-xs text-purple-700 dark:text-purple-300">
        🎨 Double-click cells to edit values. Click colored squares to change slice colors. Each row represents a slice of the pie.
      </div>
      <div v-else class="mb-2 p-2 bg-gray-50 dark:bg-gray-700/20 rounded text-xs text-gray-700 dark:text-gray-300">
        💡 Double-click cells to edit values. Use Tab/Enter to navigate between cells. Click "Expand" for a larger view.
      </div>

      <!-- Data Table -->
      <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800 relative">
        <!-- Table Header and Body in single scrollable container -->
        <div class="overflow-x-auto overflow-y-auto max-h-40 pb-10">
          <table class="w-full text-xs table-auto">
            <!-- Table Header -->
            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
              <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600 min-w-28 whitespace-nowrap">
                  {{ isScatterOrBubbleChart ? 'Points' : 'Labels' }}
                </th>
                <th
                  v-for="(dataset, index) in datasets"
                  :key="index"
                  class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600 min-w-24 relative group whitespace-nowrap"
                  :style="{ width: datasets.length <= 3 ? 'auto' : '6rem' }"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-1 flex-1">
                      <div 
                        class="w-3 h-3 rounded border border-white shadow cursor-pointer"
                        :style="{ backgroundColor: Array.isArray(dataset.backgroundColor) ? dataset.backgroundColor[0] : dataset.backgroundColor }"
                        @click="openColorPickerForDataset(index)"
                      ></div>
                      <input
                        v-model="dataset.label"
                        @input="syncTableToDatasets"
                        placeholder="Dataset"
                        class="flex-1 px-1 py-0.5 text-xs border-0 bg-transparent focus:ring-0 focus:outline-none"
                      />
                    </div>
                    <button
                      v-if="datasets.length > 1"
                      @click="removeDataset(index)"
                      class="text-red-500 hover:text-red-700 p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <XMarkIcon class="w-3 h-3" />
                    </button>
                  </div>
                  <!-- Show data format hint for scatter/bubble charts -->
                  <div v-if="isScatterOrBubbleChart" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ isBubbleChart ? 'x,y,r' : 'x,y' }}
                  </div>
                  <input
                    type="color"
                    :value="Array.isArray(dataset.backgroundColor) ? dataset.backgroundColor[0] : dataset.backgroundColor"
                    @input="updateDatasetColor(index, ($event.target as HTMLInputElement).value)"
                    class="w-0 h-0 opacity-0 absolute pointer-events-none"
                    :id="`colorPicker-${index}`"
                  />
                </th>
              </tr>
            </thead>

            <!-- Table Body -->
            <tbody>
              <tr
                v-for="(row, rowIndex) in tableData"
                :key="rowIndex"
                class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 group"
              >
                <td class="px-3 py-2 border-r border-gray-200 dark:border-gray-600 min-w-28 whitespace-nowrap">
                  <div class="flex items-center justify-between">
                    <!-- Pie/Doughnut slice color picker -->
                    <div v-if="isPieOrDoughnutChart" class="flex items-center space-x-2 flex-1">
                      <div 
                        class="w-4 h-4 rounded border border-gray-300 dark:border-gray-600 cursor-pointer shadow-sm"
                        :style="{ backgroundColor: getSliceColor(rowIndex, 0) }"
                        @click="openSliceColorPicker(rowIndex, 0)"
                      ></div>
                      <div 
                        v-if="editingLabel !== rowIndex"
                        @dblclick="startEditingLabel(rowIndex)"
                        class="flex-1 px-2 py-1 text-xs rounded cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-700 text-gray-900 dark:text-gray-100 border border-transparent transition-colors whitespace-nowrap overflow-hidden text-ellipsis"
                        :class="{ 'ring-2 ring-blue-500 ring-opacity-20': editingLabel === rowIndex }"
                        :title="row.label || `Label ${rowIndex + 1}`"
                      >
                        {{ row.label || `Label ${rowIndex + 1}` }}
                      </div>
                      <input
                        v-else
                        ref="editInputRef"
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keydown="handleEditKeyDown"
                        @input="handleEditInput"
                        class="flex-1 px-2 py-1 text-xs border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                      <input
                        type="color"
                        :value="getSliceColor(rowIndex, 0)"
                        @input="updateSliceColor(rowIndex, 0, ($event.target as HTMLInputElement).value)"
                        class="w-0 h-0 opacity-0 absolute pointer-events-none"
                        :id="`sliceColorPicker-${rowIndex}-0`"
                      />
                    </div>
                    <!-- Regular chart label -->
                    <div v-else class="flex items-center justify-between w-full">
                      <div 
                        v-if="editingLabel !== rowIndex"
                        @dblclick="startEditingLabel(rowIndex)"
                        class="flex-1 px-2 py-1 text-xs rounded cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-700 text-gray-900 dark:text-gray-100 border border-transparent transition-colors whitespace-nowrap overflow-hidden text-ellipsis"
                        :class="{ 'ring-2 ring-blue-500 ring-opacity-20': editingLabel === rowIndex }"
                        :title="row.label || `Label ${rowIndex + 1}`"
                      >
                        {{ row.label || `Label ${rowIndex + 1}` }}
                      </div>
                      <input
                        v-else
                        ref="editInputRef"
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keydown="handleEditKeyDown"
                        @input="handleEditInput"
                        class="flex-1 px-2 py-1 text-xs border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                    <button
                      v-if="tableData.length > 1"
                      @click="removeRow(rowIndex)"
                      class="ml-2 text-red-500 hover:text-red-700 p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <XMarkIcon class="w-3 h-3" />
                    </button>
                  </div>
                </td>
                <td
                  v-for="(dataset, datasetIndex) in datasets"
                  :key="datasetIndex"
                  class="px-3 py-2 border-r border-gray-200 dark:border-gray-600 min-w-24 whitespace-nowrap"
                  :style="{ width: datasets.length <= 3 ? 'auto' : '6rem' }"
                >
                  <!-- Regular charts: single number input -->
                  <div v-if="!isScatterOrBubbleChart" class="w-full">
                    <div 
                      v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex"
                      @dblclick="startEditingCell(rowIndex, datasetIndex)"
                      class="px-2 py-1 text-xs rounded cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-700 text-gray-900 dark:text-gray-100 border border-transparent transition-colors min-h-[1.5rem] flex items-center w-full whitespace-nowrap overflow-hidden text-ellipsis"
                      :title="getCellDisplayValue(row.values[datasetIndex])"
                    >
                      {{ getCellDisplayValue(row.values[datasetIndex]) }}
                    </div>
                    <input
                      v-else
                      ref="editInputRef"
                      v-model="tempEditValue"
                      @blur="saveEdit"
                      @keydown="handleEditKeyDown"
                      @input="handleEditInput"
                      type="number"
                      step="any"
                      class="w-full px-2 py-1 text-xs border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    />
                  </div>
                  
                  <!-- Scatter/Bubble charts: x, y, r inputs -->
                  <div v-else class="flex space-x-1">
                    <!-- X coordinate -->
                    <div class="flex-1">
                      <div 
                        v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex || editingCell?.field !== 'x'"
                        @dblclick="startEditingCell(rowIndex, datasetIndex, 'x')"
                        class="px-1 py-1 text-xs rounded cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-700 text-gray-900 dark:text-gray-100 text-center border border-transparent transition-colors min-h-[1.5rem] flex items-center justify-center w-full whitespace-nowrap overflow-hidden text-ellipsis"
                        :title="getCellDisplayValue(row.values[datasetIndex], 'x')"
                      >
                        {{ getCellDisplayValue(row.values[datasetIndex], 'x') }}
                      </div>
                      <input
                        v-else
                        ref="editInputRef"
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keydown="handleEditKeyDown"
                        @input="handleEditInput"
                        type="number"
                        step="any"
                        class="w-full px-1 py-1 text-xs border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                    
                    <!-- Y coordinate -->
                    <div class="flex-1">
                      <div 
                        v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex || editingCell?.field !== 'y'"
                        @dblclick="startEditingCell(rowIndex, datasetIndex, 'y')"
                        class="px-1 py-1 text-xs rounded cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-700 text-gray-900 dark:text-gray-100 text-center border border-transparent transition-colors min-h-[1.5rem] flex items-center justify-center w-full whitespace-nowrap overflow-hidden text-ellipsis"
                        :title="getCellDisplayValue(row.values[datasetIndex], 'y')"
                      >
                        {{ getCellDisplayValue(row.values[datasetIndex], 'y') }}
                      </div>
                      <input
                        v-else
                        ref="editInputRef"
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keydown="handleEditKeyDown"
                        @input="handleEditInput"
                        type="number"
                        step="any"
                        class="w-full px-1 py-1 text-xs border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                    
                    <!-- R coordinate (bubble only) -->
                    <div v-if="isBubbleChart" class="flex-1">
                      <div 
                        v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex || editingCell?.field !== 'r'"
                        @dblclick="startEditingCell(rowIndex, datasetIndex, 'r')"
                        class="px-1 py-1 text-xs rounded cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-700 text-gray-900 dark:text-gray-100 text-center border border-transparent transition-colors min-h-[1.5rem] flex items-center justify-center w-full whitespace-nowrap overflow-hidden text-ellipsis"
                        :title="getCellDisplayValue(row.values[datasetIndex], 'r')"
                      >
                        {{ getCellDisplayValue(row.values[datasetIndex], 'r') }}
                      </div>
                      <input
                        v-else
                        ref="editInputRef"
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keydown="handleEditKeyDown"
                        @input="handleEditInput"
                        type="number"
                        step="any"
                        class="w-full px-1 py-1 text-xs border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Expand Button -->
        <button
          @click="openTableModal"
          class="absolute bottom-3 right-3 p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md transition-colors"
          title="Expand table"
        >
          <ArrowsPointingOutIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- Clear Action -->
      <div class="flex justify-end mt-2">
        <button
          @click="clearAllData"
          class="px-2 py-1 text-xs text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
        >
          Clear All
        </button>
      </div>
    </div>

    <!-- Chart Options -->
    <div class="mb-4">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Options</h3>
      <div class="space-y-3">
        <!-- Title -->
        <div>
          <div class="flex items-center space-x-2 mb-1">
            <input
              type="checkbox"
              id="showTitle"
              v-model="showTitle"
              class="w-3 h-3 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            />
            <label for="showTitle" class="text-xs font-medium text-gray-700 dark:text-gray-300">Show Title</label>
          </div>
          <input
            v-if="showTitle"
            v-model="titleText"
            placeholder="Chart title"
            class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          />
        </div>

        <!-- Legend -->
        <div>
          <div class="flex items-center space-x-2 mb-1">
            <input
              type="checkbox"
              id="showLegend"
              v-model="showLegend"
              class="w-3 h-3 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            />
            <label for="showLegend" class="text-xs font-medium text-gray-700 dark:text-gray-300">Show Legend</label>
          </div>
          <select
            v-if="showLegend"
            v-model="legendPosition"
            class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          >
            <option value="top">Top</option>
            <option value="bottom">Bottom</option>
            <option value="left">Left</option>
            <option value="right">Right</option>
          </select>
        </div>

        <!-- Grid Lines -->
        <div class="flex items-center space-x-2">
          <input
            type="checkbox"
            id="showGrid"
            v-model="showGrid"
            class="w-3 h-3 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
          />
          <label for="showGrid" class="text-xs font-medium text-gray-700 dark:text-gray-300">Show Grid Lines</label>
        </div>
      </div>
    </div>

    <!-- Chart Theme -->
    <div class="mb-4">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Theme</h3>
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="text-xs text-gray-700 dark:text-gray-300">Primary Color</label>
          <div 
            class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
            :style="{ backgroundColor: theme.primary }"
            @click="openThemeColorPicker('primary')"
          ></div>
        </div>
        <div class="flex items-center justify-between">
          <label class="text-xs text-gray-700 dark:text-gray-300">Background</label>
          <div 
            class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
            :style="{ backgroundColor: theme.background }"
            @click="openThemeColorPicker('background')"
          ></div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end space-x-2 pt-4 border-t border-gray-200 dark:border-gray-700">
      <button
        @click="$emit('close')"
        class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
      >
        Cancel
      </button>
      <button
        @click="applyChanges"
        class="px-3 py-1.5 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded"
      >
        Apply
      </button>
    </div>

    <!-- Hidden color pickers -->
    <input
      type="color"
      :value="theme.primary"
      @input="updateThemeColor('primary', ($event.target as HTMLInputElement).value)"
      class="w-0 h-0 opacity-0 absolute pointer-events-none"
      ref="primaryColorPicker"
    />
    <input
      type="color"
      :value="theme.background"
      @input="updateThemeColor('background', ($event.target as HTMLInputElement).value)"
      class="w-0 h-0 opacity-0 absolute pointer-events-none"
      ref="backgroundColorPicker"
    />
  </div>

  <!-- Expanded Table Modal -->
  <div
    v-if="isModalOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click="closeTableModal"
  >
    <div
      class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] flex flex-col m-4"
      @click.stop
    >
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-600">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Edit Chart Data - {{ chartType.charAt(0).toUpperCase() + chartType.slice(1) }} Chart
        </h3>
        <div class="flex items-center space-x-2">
          <button
            @click="addDataset"
            class="px-3 py-1 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 border border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 rounded transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-1 inline" />
            Dataset
          </button>
          <button
            @click="addRow"
            class="px-3 py-1 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 border border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 rounded transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-1 inline" />
            Row
          </button>
          <button
            @click="closeTableModal"
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- Data format hint -->
      <div v-if="isScatterOrBubbleChart" class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-600">
        <p class="text-sm text-blue-700 dark:text-blue-300">
          <span v-if="isBubbleChart">💡 Double-click cells to edit. Use Tab/Enter to navigate between cells. Bubble charts use x,y,r coordinates (x=horizontal, y=vertical, r=radius)</span>
          <span v-else>💡 Double-click cells to edit. Use Tab/Enter to navigate between cells. Scatter charts use x,y coordinates (x=horizontal, y=vertical)</span>
        </p>
      </div>
      <div v-else-if="isPieOrDoughnutChart" class="p-4 bg-purple-50 dark:bg-purple-900/20 border-b border-gray-200 dark:border-gray-600">
        <p class="text-sm text-purple-700 dark:text-purple-300">
          🎨 Double-click cells to edit values. Click colored squares next to labels to change slice colors. Each row represents a slice of the pie chart.
        </p>
      </div>
      <div v-else class="p-4 bg-gray-50 dark:bg-gray-700/20 border-b border-gray-200 dark:border-gray-600">
        <p class="text-sm text-gray-700 dark:text-gray-300">
          💡 Double-click cells to edit values. Use Tab/Enter to navigate between cells. Press Escape to cancel editing.
        </p>
      </div>

      <!-- Expanded Table -->
      <div class="flex-1 overflow-auto p-4">
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600">
                  {{ isScatterOrBubbleChart ? 'Points' : 'Labels' }}
                </th>
                <th
                  v-for="(dataset, index) in datasets"
                  :key="index"
                  class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600 relative group"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 flex-1">
                      <div 
                        class="w-4 h-4 rounded border border-white shadow cursor-pointer"
                        :style="{ backgroundColor: Array.isArray(dataset.backgroundColor) ? dataset.backgroundColor[0] : dataset.backgroundColor }"
                        @click="openColorPickerForDataset(index)"
                      ></div>
                      <input
                        v-model="dataset.label"
                        @input="syncTableToDatasets"
                        placeholder="Dataset"
                        class="flex-1 px-2 py-1 text-sm border-0 bg-transparent focus:ring-0 focus:outline-none"
                      />
                    </div>
                    <button
                      v-if="datasets.length > 1"
                      @click="removeDataset(index)"
                      class="text-red-500 hover:text-red-700 p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>
                  <div v-if="isScatterOrBubbleChart" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ isBubbleChart ? 'x,y,r' : 'x,y' }}
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, rowIndex) in tableData"
                :key="rowIndex"
                class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 group"
              >
                <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-600">
                  <div class="flex items-center justify-between">
                    <div 
                      v-if="editingLabel !== rowIndex"
                      @dblclick="startEditingLabel(rowIndex)"
                      class="flex-1 px-2 py-1 text-sm rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100"
                    >
                      {{ row.label || `Label ${rowIndex + 1}` }}
                    </div>
                    <input
                      v-else
                      ref="editInputRef"
                      v-model="tempEditValue"
                      @blur="saveEdit"
                      @keydown="handleEditKeyDown"
                      @input="handleEditInput"
                      class="flex-1 px-2 py-1 text-sm border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    />
                    <button
                      v-if="tableData.length > 1"
                      @click="removeRow(rowIndex)"
                      class="ml-2 text-red-500 hover:text-red-700 p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
                <td
                  v-for="(dataset, datasetIndex) in datasets"
                  :key="datasetIndex"
                  class="px-4 py-3 border-r border-gray-200 dark:border-gray-600"
                >
                  <!-- Regular charts: single number input -->
                  <div v-if="!isScatterOrBubbleChart" class="w-full">
                    <div 
                      v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex"
                      @dblclick="startEditingCell(rowIndex, datasetIndex)"
                      class="px-2 py-1 text-sm rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 min-h-[2rem] flex items-center"
                    >
                      {{ getCellDisplayValue(row.values[datasetIndex]) }}
                    </div>
                    <input
                      v-else
                      v-model="tempEditValue"
                      @blur="saveEdit"
                      @keyup.enter="saveEdit"
                      @keyup.escape="cancelEdit"
                      type="number"
                      step="any"
                      class="w-full px-2 py-1 text-sm border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    />
                  </div>
                  
                  <!-- Scatter/Bubble charts: x, y, r inputs -->
                  <div v-else class="flex space-x-2">
                    <!-- X coordinate -->
                    <div class="flex-1">
                      <div 
                        v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex || editingCell?.field !== 'x'"
                        @dblclick="startEditingCell(rowIndex, datasetIndex, 'x')"
                        class="px-2 py-1 text-sm rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 text-center min-h-[2rem] flex items-center justify-center"
                      >
                        {{ getCellDisplayValue(row.values[datasetIndex], 'x') }}
                      </div>
                      <input
                        v-else
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keyup.enter="saveEdit"
                        @keyup.escape="cancelEdit"
                        type="number"
                        step="any"
                        class="w-full px-2 py-1 text-sm border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                    
                    <!-- Y coordinate -->
                    <div class="flex-1">
                      <div 
                        v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex || editingCell?.field !== 'y'"
                        @dblclick="startEditingCell(rowIndex, datasetIndex, 'y')"
                        class="px-2 py-1 text-sm rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 text-center min-h-[2rem] flex items-center justify-center"
                      >
                        {{ getCellDisplayValue(row.values[datasetIndex], 'y') }}
                      </div>
                      <input
                        v-else
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keyup.enter="saveEdit"
                        @keyup.escape="cancelEdit"
                        type="number"
                        step="any"
                        class="w-full px-2 py-1 text-sm border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                    
                    <!-- R coordinate (bubble only) -->
                    <div v-if="isBubbleChart" class="flex-1">
                      <div 
                        v-if="editingCell?.row !== rowIndex || editingCell?.col !== datasetIndex || editingCell?.field !== 'r'"
                        @dblclick="startEditingCell(rowIndex, datasetIndex, 'r')"
                        class="px-2 py-1 text-sm rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 text-center min-h-[2rem] flex items-center justify-center"
                      >
                        {{ getCellDisplayValue(row.values[datasetIndex], 'r') }}
                      </div>
                      <input
                        v-else
                        v-model="tempEditValue"
                        @blur="saveEdit"
                        @keyup.enter="saveEdit"
                        @keyup.escape="cancelEdit"
                        type="number"
                        step="any"
                        class="w-full px-2 py-1 text-sm border border-blue-500 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-between p-4 border-t border-gray-200 dark:border-gray-600">
        <button
          @click="clearAllData"
          class="px-3 py-1 text-sm text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
        >
          Clear All Data
        </button>
        <button
          @click="closeTableModal"
          class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded"
        >
          Done
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, computed, watch } from 'vue'
import { 
  ChartBarIcon, 
  XMarkIcon, 
  PlusIcon,
  ChartPieIcon,
  ArrowTrendingUpIcon,
  CircleStackIcon,
  ArrowsPointingOutIcon
} from '@heroicons/vue/24/outline'
import type { ChartLayerProperties, ChartTheme, ScatterDataPoint, BubbleDataPoint } from '@/types'

interface Props {
  properties: ChartLayerProperties
}

const props = defineProps<Props>()

const emit = defineEmits<{
  update: [properties: Partial<ChartLayerProperties>]
  close: []
}>()

console.log('ChartLayerEditor initialized with properties:', props.properties)

// Chart types configuration
const chartTypes = [
  { value: 'bar', label: 'Bar', icon: ChartBarIcon },
  { value: 'line', label: 'Line', icon: ArrowTrendingUpIcon },
  { value: 'pie', label: 'Pie', icon: ChartPieIcon },
  { value: 'doughnut', label: 'Doughnut', icon: CircleStackIcon },
  { value: 'area', label: 'Area', icon: ArrowTrendingUpIcon },
  { value: 'scatter', label: 'Scatter', icon: CircleStackIcon },
  { value: 'bubble', label: 'Bubble', icon: CircleStackIcon }
]

// Local state
const chartType = ref(props.properties.chartType || 'bar')
const datasets = ref(
  props.properties.data?.datasets?.map(dataset => ({
    ...dataset,
    // Preserve array colors for pie/doughnut charts, flatten for others
    backgroundColor: (props.properties.chartType === 'pie' || props.properties.chartType === 'doughnut') 
      ? dataset.backgroundColor 
      : (Array.isArray(dataset.backgroundColor) ? dataset.backgroundColor[0] : (dataset.backgroundColor || '#3B82F6')),
    borderColor: (props.properties.chartType === 'pie' || props.properties.chartType === 'doughnut')
      ? dataset.borderColor
      : (Array.isArray(dataset.borderColor) ? dataset.borderColor[0] : (dataset.borderColor || '#1E40AF'))
  })) || [
    { 
      label: 'Sales', 
      data: [10, 20, 30, 40] as number[],
      backgroundColor: '#3B82F6',
      borderColor: '#1E40AF'
    },
    { 
      label: 'Revenue', 
      data: [15, 25, 35, 45] as number[],
      backgroundColor: '#10B981',
      borderColor: '#059669'
    }
  ]
)

// Chart options
const showTitle = ref(props.properties.options?.plugins?.title?.display || false)
const titleText = ref(props.properties.options?.plugins?.title?.text || '')
const showLegend = ref(props.properties.options?.plugins?.legend?.display !== false)
const legendPosition = ref(props.properties.options?.plugins?.legend?.position || 'top')
const showGrid = ref(props.properties.options?.scales?.y?.grid?.display !== false)

// Table data structure
interface TableRow {
  label: string
  values: (number | ScatterDataPoint | BubbleDataPoint)[]
}

// Computed property to check chart type
const isScatterChart = computed(() => chartType.value === 'scatter')
const isBubbleChart = computed(() => chartType.value === 'bubble')
const isScatterOrBubbleChart = computed(() => isScatterChart.value || isBubbleChart.value)
const isPieOrDoughnutChart = computed(() => chartType.value === 'pie' || chartType.value === 'doughnut')

const tableData = ref<TableRow[]>([])

// Get slice color for pie/doughnut charts
const getSliceColor = (rowIndex: number, datasetIndex: number): string => {
  if (!isPieOrDoughnutChart.value) return '#3B82F6'
  
  const dataset = datasets.value[datasetIndex]
  const bgColors = dataset.backgroundColor
  
  if (Array.isArray(bgColors) && bgColors.length > rowIndex) {
    return bgColors[rowIndex] || '#3B82F6'
  }
  
  // If backgroundColor is a single color, return it for all slices
  if (typeof bgColors === 'string') {
    return bgColors
  }
  
  // Fallback: use theme accent colors
  const colors = theme.value.accent || [
    '#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899',
    '#06B6D4', '#84CC16', '#F472B6', '#A78BFA'
  ]
  return colors[rowIndex % colors.length]
}

// Update slice color for pie/doughnut charts
const updateSliceColor = (rowIndex: number, datasetIndex: number, color: string) => {
  if (!isPieOrDoughnutChart.value) return
  
  const dataset = datasets.value[datasetIndex] as any
  
  if (!Array.isArray(dataset.backgroundColor)) {
    // Convert to array if not already
    const newColors = tableData.value.map((_, index) => 
      index === rowIndex ? color : getSliceColor(index, datasetIndex)
    )
    dataset.backgroundColor = newColors
  } else {
    // Update specific slice color
    dataset.backgroundColor[rowIndex] = color
  }
  
  syncTableToDatasets()
}

// Initialize table data from existing datasets
const initializeTableData = () => {
  if (props.properties.data?.labels && props.properties.data.labels.length > 0) {
    // Use existing labels and data
    tableData.value = props.properties.data.labels.map((label, index) => ({
      label: String(label),
      values: datasets.value.map(dataset => {
        const dataPoint = dataset.data[index]
        if (isScatterOrBubbleChart.value) {
          // Handle scatter/bubble data points
          if (typeof dataPoint === 'object' && dataPoint !== null && 'x' in dataPoint && 'y' in dataPoint) {
            return dataPoint as ScatterDataPoint | BubbleDataPoint
          }
          return isBubbleChart.value 
            ? { x: 0, y: 0, r: 5 } as BubbleDataPoint
            : { x: 0, y: 0 } as ScatterDataPoint
        } else {
          // Handle regular numeric data
          return typeof dataPoint === 'number' ? dataPoint : 0
        }
      })
    }))
  } else {
    // Create default table with sample data
    const defaultLabels = ['Q1', 'Q2', 'Q3', 'Q4']
    tableData.value = defaultLabels.map((label, index) => ({
      label,
      values: datasets.value.map(dataset => {
        const dataPoint = dataset.data[index]
        if (isScatterOrBubbleChart.value) {
          if (typeof dataPoint === 'object' && dataPoint !== null && 'x' in dataPoint && 'y' in dataPoint) {
            return dataPoint as ScatterDataPoint | BubbleDataPoint
          }
          return isBubbleChart.value 
            ? { x: index * 10, y: Math.random() * 100, r: 5 } as BubbleDataPoint
            : { x: index * 10, y: Math.random() * 100 } as ScatterDataPoint
        } else {
          return typeof dataPoint === 'number' ? dataPoint : Math.floor(Math.random() * 50) + 10
        }
      })
    }))
  }
  
  // Ensure we have at least one row
  if (tableData.value.length === 0) {
    tableData.value = [
      { 
        label: 'Point 1', 
        values: datasets.value.map(() => 
          isScatterOrBubbleChart.value 
            ? (isBubbleChart.value ? { x: 0, y: 0, r: 5 } : { x: 0, y: 0 })
            : 0
        ) 
      }
    ]
  }
}

// Initialize the table data
initializeTableData()

// Watch for chart type changes and reinitialize data structure
watch(chartType, () => {
  // Initialize pie/doughnut slice colors if needed (only if not already set)
  if (isPieOrDoughnutChart.value) {
    datasets.value.forEach(dataset => {
      if (!Array.isArray(dataset.backgroundColor) || (dataset.backgroundColor as string[]).length === 0) {
        // Only initialize colors if they don't exist or are empty
        const colors = theme.value.accent || [
          '#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899',
          '#06B6D4', '#84CC16', '#F472B6', '#A78BFA'
        ]
        ;(dataset as any).backgroundColor = tableData.value.map((_, index) => 
          colors[index % colors.length]
        )
      }
    })
  }
  
  // Convert existing data to new format when chart type changes
  tableData.value.forEach(row => {
    row.values = row.values.map((value, index) => {
      if (isBubbleChart.value) {
        // Convert to bubble data
        if (typeof value === 'object' && value !== null && 'x' in value && 'y' in value) {
          return { x: value.x, y: value.y, r: 'r' in value ? value.r : 5 } as BubbleDataPoint
        }
        return { x: 0, y: typeof value === 'number' ? value : 0, r: 5 } as BubbleDataPoint
      } else if (isScatterChart.value) {
        // Convert to scatter data
        if (typeof value === 'object' && value !== null && 'x' in value && 'y' in value) {
          return { x: value.x, y: value.y } as ScatterDataPoint
        }
        return { x: 0, y: typeof value === 'number' ? value : 0 } as ScatterDataPoint
      } else {
        // Convert to number
        if (typeof value === 'number') {
          return value
        }
        if (typeof value === 'object' && value !== null && 'y' in value) {
          return value.y
        }
        return 0
      }
    })
  })
  
  // Update datasets with new data structure
  syncTableToDatasets()
})

// Theme
const theme = ref<ChartTheme>({
  primary: props.properties.theme?.primary || '#3B82F6',
  secondary: props.properties.theme?.secondary || '#8B5CF6',
  background: props.properties.theme?.background || '#FFFFFF',
  text: props.properties.theme?.text || '#1F2937',
  grid: props.properties.theme?.grid || '#E5E7EB',
  accent: props.properties.theme?.accent || ['#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899'],
  tooltip: props.properties.theme?.tooltip || {
    background: '#1F2937',
    text: '#FFFFFF'
  }
})

// Template refs
const primaryColorPicker = ref<HTMLInputElement>()
const backgroundColorPicker = ref<HTMLInputElement>()

// Modal and editing state
const isModalOpen = ref(false)
const editingCell = ref<{ row: number; col: number; field?: 'x' | 'y' | 'r' } | null>(null)
const editingLabel = ref<number | null>(null)
const tempEditValue = ref<string>('')
const editInputRef = ref<HTMLInputElement>()

// Add keyboard navigation
const handleKeyDown = (event: KeyboardEvent) => {
  if (!editingCell.value && !editingLabel.value) return
  
  const { key } = event
  
  if (key === 'Tab') {
    event.preventDefault()
    if (editingCell.value) {
      moveToNextCell()
    }
  } else if (key === 'Enter') {
    event.preventDefault()
    saveEdit()
    if (editingCell.value) {
      moveToNextCell()
    }
  } else if (key === 'Escape') {
    event.preventDefault()
    cancelEdit()
  }
}

const moveToNextCell = () => {
  if (!editingCell.value) return
  
  const { row, col, field } = editingCell.value
  
  // For scatter/bubble charts, move through x, y, r fields first
  if (isScatterOrBubbleChart.value) {
    if (field === 'x') {
      startEditingCell(row, col, 'y')
      return
    } else if (field === 'y' && isBubbleChart.value) {
      startEditingCell(row, col, 'r')
      return
    }
  }
  
  // Move to next column
  if (col < datasets.value.length - 1) {
    const nextField = isScatterOrBubbleChart.value ? 'x' : undefined
    startEditingCell(row, col + 1, nextField)
  } else if (row < tableData.value.length - 1) {
    // Move to next row, first column
    const nextField = isScatterOrBubbleChart.value ? 'x' : undefined
    startEditingCell(row + 1, 0, nextField)
  } else {
    // End of table
    cancelEdit()
  }
}

// Methods
const updateProperty = (key: keyof ChartLayerProperties, value: any) => {
  if (key === 'chartType') {
    const newChartType = value as ChartLayerProperties['chartType']
    const oldChartType = chartType.value
    chartType.value = newChartType
    
    // Convert data structure when chart type changes
    convertChartTypeData(oldChartType, newChartType)
  }
}

// Chart type conversion utility
const convertChartTypeData = (oldType: string, newType: string) => {
  const needsCoordinates = ['scatter', 'bubble'].includes(newType)
  const oldNeedsCoordinates = ['scatter', 'bubble'].includes(oldType)
  const isPieOrDoughnut = ['pie', 'doughnut'].includes(newType)
  const oldIsPieOrDoughnut = ['pie', 'doughnut'].includes(oldType)
  
  // Update datasets structure
  datasets.value.forEach((dataset, datasetIndex) => {
    if (needsCoordinates && !oldNeedsCoordinates) {
      // Convert from regular chart to scatter/bubble
      const newData = (dataset.data as number[]).map((value, index) => {
        if (newType === 'bubble') {
          return { x: index, y: value, r: 5 } as BubbleDataPoint
        } else {
          return { x: index, y: value } as ScatterDataPoint
        }
      })
      dataset.data = newData
    } else if (!needsCoordinates && oldNeedsCoordinates) {
      // Convert from scatter/bubble to regular chart
      const newData = (dataset.data as (ScatterDataPoint | BubbleDataPoint)[]).map(point => {
        return typeof point === 'object' ? point.y : 0
      })
      dataset.data = newData
    }
    
    // Handle pie/doughnut specific backgroundColor
    if (isPieOrDoughnut && !oldIsPieOrDoughnut) {
      // Convert to multi-color for pie/doughnut slices
      // Only create new colors if no existing backgroundColor array
      if (!Array.isArray(dataset.backgroundColor)) {
        const colors = theme.value.accent || [
          '#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899',
          '#06B6D4', '#84CC16', '#F472B6', '#A78BFA'
        ]
        ;(dataset as any).backgroundColor = tableData.value.map((_, index) => 
          colors[index % colors.length]
        )
      }
    } else if (!isPieOrDoughnut && oldIsPieOrDoughnut) {
      // Convert back to single color
      const currentBg = dataset.backgroundColor
      dataset.backgroundColor = Array.isArray(currentBg) ? currentBg[0] : currentBg
    }
  })
  
  // Update table data structure
  tableData.value.forEach(row => {
    row.values = row.values.map((value, datasetIndex) => {
      if (needsCoordinates && !oldNeedsCoordinates) {
        // Convert to coordinate data
        const numValue = typeof value === 'number' ? value : 0
        if (newType === 'bubble') {
          return { x: 0, y: numValue, r: 5 } as BubbleDataPoint
        } else {
          return { x: 0, y: numValue } as ScatterDataPoint
        }
      } else if (!needsCoordinates && oldNeedsCoordinates) {
        // Convert to number data
        return typeof value === 'object' && value !== null && 'y' in value ? value.y : 0
      }
      return value
    })
  })
  
  syncTableToDatasets()
}

const addColumn = () => {
  const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316']
  const color = colors[datasets.value.length % colors.length]
  
  datasets.value.push({
    label: `Dataset ${datasets.value.length + 1}`,
    data: tableData.value.map(() => 0),
    backgroundColor: color,
    borderColor: color
  })
  
  // Add values for all existing rows
  tableData.value.forEach(row => {
    row.values.push(0)
  })
}

const addDataset = () => {
  const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316']
  const color = colors[datasets.value.length % colors.length]
  
  // Create appropriate data based on chart type
  let newData: number[] | ScatterDataPoint[] | BubbleDataPoint[]
  
  if (isBubbleChart.value) {
    newData = tableData.value.map(() => ({ x: 0, y: 0, r: 5 })) as BubbleDataPoint[]
  } else if (isScatterChart.value) {
    newData = tableData.value.map(() => ({ x: 0, y: 0 })) as ScatterDataPoint[]
  } else {
    newData = tableData.value.map(() => 0) as number[]
  }
  
  datasets.value.push({
    label: `Dataset ${datasets.value.length + 1}`,
    data: newData,
    backgroundColor: color,
    borderColor: color
  })
  
  // Add values for all existing rows
  tableData.value.forEach(row => {
    if (isBubbleChart.value) {
      row.values.push({ x: 0, y: 0, r: 5 } as BubbleDataPoint)
    } else if (isScatterChart.value) {
      row.values.push({ x: 0, y: 0 } as ScatterDataPoint)
    } else {
      row.values.push(0)
    }
  })
}

const addRow = () => {
  const newValues: (number | ScatterDataPoint | BubbleDataPoint)[] = datasets.value.map(() => {
    if (isBubbleChart.value) {
      return { x: 0, y: 0, r: 5 } as BubbleDataPoint
    } else if (isScatterChart.value) {
      return { x: 0, y: 0 } as ScatterDataPoint
    } else {
      return 0
    }
  })
  
  tableData.value.push({
    label: isScatterOrBubbleChart.value ? `Point ${tableData.value.length + 1}` : `Label ${tableData.value.length + 1}`,
    values: newValues
  })
}

const removeDataset = (index: number) => {
  if (datasets.value.length > 1) {
    datasets.value.splice(index, 1)
    tableData.value.forEach(row => {
      row.values.splice(index, 1)
    })
  }
}

const removeRow = (index: number) => {
  if (tableData.value.length > 1) {
    tableData.value.splice(index, 1)
  }
}

const updateDatasetColor = (index: number, color: string) => {
  datasets.value[index].backgroundColor = color
  datasets.value[index].borderColor = color
}

const openColorPickerForDataset = (index: number) => {
  nextTick(() => {
    const picker = document.getElementById(`colorPicker-${index}`) as HTMLInputElement
    picker?.click()
  })
}

const openSliceColorPicker = (rowIndex: number, datasetIndex: number) => {
  nextTick(() => {
    const picker = document.getElementById(`sliceColorPicker-${rowIndex}-${datasetIndex}`) as HTMLInputElement
    picker?.click()
  })
}

const openThemeColorPicker = (type: 'primary' | 'background') => {
  nextTick(() => {
    if (type === 'primary') {
      primaryColorPicker.value?.click()
    } else if (type === 'background') {
      backgroundColorPicker.value?.click()
    }
  })
}

const updateThemeColor = (type: string, color: string) => {
  if (type === 'primary') {
    theme.value.primary = color
  } else if (type === 'background') {
    theme.value.background = color
  }
}

// Cell editing methods
const startEditingCell = (rowIndex: number, colIndex: number, field?: 'x' | 'y' | 'r') => {
  const row = tableData.value[rowIndex]
  const value = row.values[colIndex]
  
  if (field && typeof value === 'object' && value !== null) {
    if (field === 'r' && 'r' in value) {
      tempEditValue.value = String((value as BubbleDataPoint).r || 0)
    } else if (field === 'x' || field === 'y') {
      tempEditValue.value = String(value[field] || 0)
    } else {
      tempEditValue.value = '0'
    }
  } else {
    tempEditValue.value = String(typeof value === 'number' ? value : 0)
  }
  
  editingCell.value = { row: rowIndex, col: colIndex, field }
  
  // Focus and select the input after DOM update
  nextTick(() => {
    const input = editInputRef.value
    if (input) {
      input.focus()
      input.select()
    }
  })
}

const startEditingLabel = (rowIndex: number) => {
  tempEditValue.value = tableData.value[rowIndex].label
  editingLabel.value = rowIndex
  
  // Focus and select the input after DOM update
  nextTick(() => {
    const input = editInputRef.value
    if (input) {
      input.focus()
      input.select()
    }
  })
}

const saveEdit = () => {
  if (editingCell.value) {
    const { row, col, field } = editingCell.value
    
    // Validate numeric input
    let numValue = parseFloat(tempEditValue.value)
    if (isNaN(numValue)) {
      numValue = 0
    }
    
    if (field && typeof tableData.value[row].values[col] === 'object') {
      // Update scatter/bubble point field
      const point = tableData.value[row].values[col] as ScatterDataPoint | BubbleDataPoint
      if (field === 'r' && 'r' in point) {
        // Ensure radius is positive
        (point as BubbleDataPoint).r = Math.max(0.1, numValue)
      } else if (field === 'x' || field === 'y') {
        point[field] = numValue
      }
    } else {
      // Update regular number value
      tableData.value[row].values[col] = numValue
    }
    
    syncTableToDatasets()
  }
  
  if (editingLabel.value !== null) {
    // Ensure label is not empty
    const newLabel = tempEditValue.value.trim()
    tableData.value[editingLabel.value].label = newLabel || `Label ${editingLabel.value + 1}`
    syncTableToDatasets()
  }
  
  editingCell.value = null
  editingLabel.value = null
  tempEditValue.value = ''
}

const cancelEdit = () => {
  editingCell.value = null
  editingLabel.value = null
  tempEditValue.value = ''
}

const handleEditKeyDown = (event: KeyboardEvent) => {
  handleKeyDown(event)
}

const handleEditInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  tempEditValue.value = target.value
}

const quickEditCell = (rowIndex: number, colIndex: number, field?: 'x' | 'y' | 'r') => {
  // Quick edit with Enter to save and move to next cell
  startEditingCell(rowIndex, colIndex, field)
}

const getCellDisplayValue = (value: number | ScatterDataPoint | BubbleDataPoint, field?: 'x' | 'y' | 'r'): string => {
  if (field && typeof value === 'object' && value !== null) {
    if (field === 'r' && 'r' in value) {
      return String((value as BubbleDataPoint).r || 0)
    } else if (field === 'x' || field === 'y') {
      return String(value[field] || 0)
    }
    return '0'
  }
  return String(typeof value === 'number' ? value : 0)
}

const openTableModal = () => {
  isModalOpen.value = true
}

const closeTableModal = () => {
  isModalOpen.value = false
  cancelEdit()
}

const syncTableToDatasets = () => {
  // Sync table data to datasets based on chart type
  datasets.value.forEach((dataset, datasetIndex) => {
    if (isScatterOrBubbleChart.value) {
      // For scatter/bubble charts, data should be points with x, y (and r for bubble)
      dataset.data = tableData.value.map(row => {
        const value = row.values[datasetIndex]
        if (typeof value === 'object' && value !== null && 'x' in value && 'y' in value) {
          return value as ScatterDataPoint | BubbleDataPoint
        }
        // Default point if value is not properly structured
        return isBubbleChart.value 
          ? { x: 0, y: 0, r: 5 } as BubbleDataPoint
          : { x: 0, y: 0 } as ScatterDataPoint
      })
    } else {
      // For regular charts, data should be numbers
      dataset.data = tableData.value.map(row => {
        const value = row.values[datasetIndex]
        return typeof value === 'number' ? value : 0
      })
    }
  })
}

const clearAllData = () => {
  tableData.value.forEach(row => {
    row.values = row.values.map(() => {
      if (isBubbleChart.value) {
        return { x: 0, y: 0, r: 5 } as BubbleDataPoint
      } else if (isScatterChart.value) {
        return { x: 0, y: 0 } as ScatterDataPoint
      } else {
        return 0
      }
    })
  })
  syncTableToDatasets()
}

const applyChanges = () => {
  syncTableToDatasets()
  
  const chartProperties: Partial<ChartLayerProperties> = {
    chartType: chartType.value,
    data: {
      labels: tableData.value.map(row => row.label),
      datasets: datasets.value.map(dataset => ({
        ...dataset,
        data: dataset.data
      }))
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: showTitle.value,
          text: titleText.value
        },
        legend: {
          display: showLegend.value,
          position: legendPosition.value
        }
      }
    } as any,
    theme: theme.value
  }
  
  emit('update', chartProperties)
}
</script>
