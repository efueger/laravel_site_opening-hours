export const modal = {
  email: null,
  label: null,
  srv: null,
  strict: null,
  text: null
}

export default {
  data() {
    return {
      modal
    }
  },
  computed: {
    modalActive() {
      for (const key in this.modal) {
        if (key !== 'srv' && this.modal[key]) {
          return true
        }
      }
    }
  },
  methods: {
    modalClose() {
      for (const key in this.modal) {
        this.modal[key] = null
      }
    },
    requestService() {
      console.log('req')
      this.modal.text = 'requestService'
    },
    newChannel(srv) {
      this.modal.text = 'newChannel'
      this.modal.srv = srv
    },
    newUser(srv) {
      this.modal.text = 'newUser'
      this.modal.srv = srv
    },
    newRole(srv) {
      this.modal.text = 'newRole'
      this.modal.srv = srv
    }
  }
}
