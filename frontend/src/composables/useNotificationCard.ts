import { ref } from 'vue'

interface NotificationOptions {
  type?: 'success' | 'error' | 'warning' | 'info'
  title: string
  message: string
  buttonText?: string
  autoClose?: boolean
  duration?: number
}

const notification = ref<NotificationOptions | null>(null)
const isVisible = ref(false)

export function useNotificationCard() {
  const show = (options: NotificationOptions) => {
    notification.value = options
    isVisible.value = true
  }

  const hide = () => {
    isVisible.value = false
    setTimeout(() => {
      notification.value = null
    }, 300)
  }

  const success = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    show({
      type: 'success',
      title,
      message,
      buttonText: 'OK',
      autoClose: false,
      ...options
    })
  }

  const error = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    show({
      type: 'error',
      title,
      message,
      buttonText: 'OK',
      autoClose: false,
      ...options
    })
  }

  const warning = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    show({
      type: 'warning',
      title,
      message,
      buttonText: 'OK',
      autoClose: false,
      ...options
    })
  }

  const info = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    show({
      type: 'info',
      title,
      message,
      buttonText: 'OK',
      autoClose: false,
      ...options
    })
  }

  return {
    notification,
    isVisible,
    show,
    hide,
    success,
    error,
    warning,
    info
  }
}
