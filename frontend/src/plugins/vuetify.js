import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'clinicLight',
    themes: {
      clinicLight: {
        dark: false,
        colors: {
          background: '#f6f8fb',
          surface: '#ffffff',
          primary: '#2563eb',
          secondary: '#0f766e',
          error: '#b42318',
          warning: '#b54708',
          success: '#047857',
        },
      },
    },
  },
})
