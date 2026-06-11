<template>
  <div class="timer-process-container">
    <el-progress type="circle" :percentage="percentage" :width="size" :stroke-width="strokeWidth" :color="progressColor"
      :status="progressStatus" :format="format">
      <div class="time-display">
        <span class="time-text">{{ timeDisplay }}</span>
      </div>
    </el-progress>
  </div>
</template>

<script setup lang="ts">
  import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
  import { ElProgress } from 'element-plus'

  const props = defineProps({
    startTime: {
      type: String,
      required: true
    },
    duration: {
      type: Number,
      default: 1800,
    },
    size: {
      type: Number,
      default: 32,
      required: false
    },
    strokeWidth: {
      type: Number,
      default: 16,
      required: false
    },
    defaultColor: {
      type: String,
      required: false,
      default: "#25C222",
    },

    completedColor: {
      type: String,
      required: false,
      default: "#25C222",
    }
  });

  const emit = defineEmits<{
    complete: []
  }>()

  // 已过去时间（秒）
  const elapsedSeconds = ref<number>(0)
  // 定时器ID
  const timerId = ref<number | null>(null)

  // 计算当前已过去的时间
  const calculateElapsed = (): number => {
    const now = Date.now();
    const elapsedMs = now - new Date(props.startTime).getTime();
    return Math.min(props.duration, Math.floor(elapsedMs / 1000))
  }

  // 计算百分比
  const percentage = computed(() => {
    if (elapsedSeconds.value >= props.duration) return 100
    return Math.round((elapsedSeconds.value / props.duration) * 100)
  })

  // 计算进度条颜色
  const progressColor = computed(() => {
    return elapsedSeconds.value >= props.duration ? props.completedColor : props.defaultColor
  })

  // 计算进度条状态
  const progressStatus = computed(() => {
    if (elapsedSeconds.value >= props.duration) return 'success'
    return undefined
  })

  // 格式化时间显示
  const timeDisplay = computed(() => {
    const remainingSeconds = props.duration - elapsedSeconds.value

    if (remainingSeconds <= 0) return '00:00'

    const hours = Math.floor(remainingSeconds / 3600)
    const minutes = Math.floor((remainingSeconds % 3600) / 60)
    const seconds = remainingSeconds % 60

    if (hours > 0) {
      return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    }

    // 如果不需要显示小时或者小时为0，则只显示分钟和秒
    if (hours > 0) {
      const totalMinutes = Math.floor(remainingSeconds / 60)
      return `${totalMinutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    }

    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
  })

  // 格式函数（用于进度条内部显示）
  const format = () => ''

  // 开始计时
  const startTimer = () => {
    // 初始化已过去时间
    elapsedSeconds.value = calculateElapsed()

    // 如果已经存在定时器，先清除
    if (timerId.value) {
      clearInterval(timerId.value)
    }

    // 启动计时
    timerId.value = window.setInterval(() => {
      elapsedSeconds.value = calculateElapsed()

      if (elapsedSeconds.value >= props.duration) {
        // 计时完成
        clearInterval(timerId.value!)
        timerId.value = null
        elapsedSeconds.value = props.duration
        emit('complete')
      }
    }, 1000)
  }

  // 停止计时
  const stopTimer = () => {
    if (timerId.value) {
      clearInterval(timerId.value)
      timerId.value = null
    }
  }

  // 重置计时器
  const resetTimer = () => {
    stopTimer()
    elapsedSeconds.value = 0
    startTimer()
  }

  // 监听开始时间变化
  watch(
    () => props.startTime,
    () => {
      resetTimer()
    }
  )

  onMounted(() => {
    startTimer()
  })

  onUnmounted(() => {
    stopTimer()
  })
</script>

<style scoped>
  .timer-process-container {
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .time-display {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
  }

  .time-text {
    font-size: v-bind('`${Math.max(size / 5, 14)}px`');
    font-weight: bold;
    color: #606266;
  }
</style>