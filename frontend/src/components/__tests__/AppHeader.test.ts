import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppHeader from '../common/AppHeader.vue'

describe('AppHeader', () => {
  it('renders title correctly', () => {
    const wrapper = mount(AppHeader, {
      props: { 
        title: 'Test Title'
      }
    })
    
    expect(wrapper.find('h1').text()).toBe('Test Title')
    expect(wrapper.find('.header-title').exists()).toBe(true)
  })

  it('renders subtitle when provided', () => {
    const wrapper = mount(AppHeader, {
      props: { 
        title: 'Test Title',
        subtitle: 'Test Subtitle'
      }
    })
    
    expect(wrapper.find('.header-subtitle').text()).toBe('Test Subtitle')
  })

  it('does not render subtitle when not provided', () => {
    const wrapper = mount(AppHeader, {
      props: { 
        title: 'Test Title'
      }
    })
    
    expect(wrapper.find('.header-subtitle').exists()).toBe(false)
  })

  it('shows back button when showBackButton is true', () => {
    const wrapper = mount(AppHeader, {
      props: { 
        title: 'Test Title',
        showBackButton: true
      }
    })
    
    expect(wrapper.find('[data-testid="back-button"]').exists()).toBe(true)
    expect(wrapper.find('button').text()).toContain('Retour')
  })

  it('does not show back button when showBackButton is false', () => {
    const wrapper = mount(AppHeader, {
      props: { 
        title: 'Test Title',
        showBackButton: false
      }
    })
    
    expect(wrapper.find('[data-testid="back-button"]').exists()).toBe(false)
  })

  it('has correct aria-label for back button', () => {
    const wrapper = mount(AppHeader, {
      props: { 
        title: 'Test Title',
        showBackButton: true,
        backButtonText: 'Retour',
        backRoute: '/dashboard'
      }
    })
    
    const backButton = wrapper.find('[data-testid="back-button"]')
    expect(backButton.attributes('aria-label')).toBe('Retour vers /dashboard')
  })
})
