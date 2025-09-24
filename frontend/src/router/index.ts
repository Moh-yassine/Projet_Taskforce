import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import SignupView from '../views/SignupView.vue'
import DashboardView from '../views/DashboardView.vue'
import TasksView from '../views/TasksView.vue'
import ProjectTasksView from '../views/ProjectTasksView.vue'
import MyTasksView from '../views/MyTasksView.vue'
import AdminView from '../views/AdminView.vue'
import UsersView from '../views/UsersView.vue'
import ReportsView from '../views/ReportsView.vue'
import AllTasksView from '../views/AllTasksView.vue'
import NotificationsView from '../views/NotificationsView.vue'
import PremiumFeaturesView from '../views/PremiumFeaturesView.vue'
import PaymentView from '../views/PaymentView.vue'
import ObserverDemoView from '../views/ObserverDemoView.vue'
import ObserverModeView from '../views/ObserverModeView.vue'
import RoleManagement from '../components/RoleManagement.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/signup',
      name: 'signup',
      component: SignupView,
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
    },
    {
      path: '/tasks',
      name: 'tasks',
      component: TasksView,
    },
    {
      path: '/project/:id/tasks',
      name: 'project-tasks',
      component: ProjectTasksView,
    },
    {
      path: '/my-tasks',
      name: 'my-tasks',
      component: MyTasksView,
    },
    {
      path: '/admin',
      name: 'admin',
      component: AdminView,
    },
    {
      path: '/users',
      name: 'users',
      component: UsersView,
    },
    {
      path: '/roles',
      name: 'roles',
      component: RoleManagement,
    },
    {
      path: '/reports',
      name: 'reports',
      component: ReportsView,
    },
    {
      path: '/all-tasks',
      name: 'all-tasks',
      component: AllTasksView,
    },
    {
      path: '/notifications',
      name: 'notifications',
      component: NotificationsView,
    },
    {
      path: '/premium',
      name: 'premium',
      component: PremiumFeaturesView,
    },
    {
      path: '/payment',
      name: 'payment',
      component: PaymentView,
    },
    {
      path: '/payment/success',
      name: 'payment-success',
      component: PaymentView,
    },
    {
      path: '/observer-demo',
      name: 'observer-demo',
      component: ObserverDemoView,
    },
    {
      path: '/observer-mode',
      name: 'observer-mode',
      component: ObserverModeView,
    },
  ],
})

export default router
