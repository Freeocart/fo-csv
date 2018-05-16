/*
  Util import/export mixin
*/
import { createNamespacedHelpers } from 'vuex'

export default function (module) {
  const { mapState, mapGetters, mapActions } = createNamespacedHelpers(module)

  return {
    computed: {
      ...mapState([
        'data'
      ]),
      ...mapGetters([
        'profile',
        'profiles'
      ])
    },
    methods: {
      restoreToProfile ({ name, profile }) {
        this.applyProfile({ name, profile })
        this.saveNewProfile(name)
      },
      deleteProfile (name) {
        if (confirm(this.$t('Are you sure you want remove this item?'))) {
          if (this.currentProfileName === name) {
            this.setCurrentProfileName('default')
          }

          this.deleteProfile(name)
          this.saveAllProfiles(this.profiles)
        }
      },
      restoreProfiles (profiles) {
        if (confirm(this.$t('Are you sure? This will remove all profiles before trying to add new ones!'))) {
          this.saveAllProfiles(profiles)
        }
      },
      ...mapActions([
        'deleteProfile',
        'saveAllProfiles',
        'setCurrentProfileName',
        'applyProfile',
        'saveNewProfile'
      ])
    }
  }
}
