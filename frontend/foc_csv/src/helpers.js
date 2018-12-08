import papaparse from 'papaparse'

/*
  Validate required fields in app config
*/
const validateAppConfig = (config) => {
  const requiredFields = ['token', 'baseRoute', 'baseUrl']

  const keys = Object.keys(config)
  const values = Object.values(config)

  if (requiredFields.every(key => keys.includes(key)) &&
      values.every(value => !!value)) {
    return true
  }

  return false
}

/*
  Read first string from blob
*/
class FirstLineReader {
  constructor () {
    this.events = {}

    this.chunkSize = 256
    this.readPos = 0
    this.reader = new FileReader()
    this.lines = []
    this.chunk = ''
    this.file = null
    this.readedLines = 0
    this.skippedBytes = 0

    this.reader.onload = () => {
      // it seems that broken symbols automaticaly converts to \uFFFD
      // so we just check line endings and try to read again
      // one byte more
      if (/\uFFFD$/.test(this.reader.result)) {
        this.skippedBytes++
        this.step()
      }
      else {
        this.readPos += this.chunkSize + this.skippedBytes
        this.skippedBytes = 0
        this.chunk += this.reader.result
        this.process()
      }
    }
  }

  on (event, cb) {
    this.events[event] = cb
  }

  _emit (event, args) {
    if (typeof this.events[event] === 'function') {
      this.events[event].apply(this, args)
    }
  }

  process () {
    if (/[\r\n]+$/.test(this.chunk)) {
      const lines = this.chunk.split('\n')
      const line = lines.shift()

      this.readedLines++

      if (this.readedLines === this.skipLines) {
        this._emit('line', [line])
      }
      else {
        this.chunk = lines.join('\n')
        this.step()
      }
    }
    else {
      if (this.readPos < this.file.size) {
        this.step()
      }
      else {
        this._emit('error')
      }
    }
  }

  read (file, encoding, skipLines = 1) {
    this.file = file
    this.lines = []
    this.chunk = ''
    this.readPos = 0
    this.encoding = encoding || 'UTF8'
    this.skipLines = parseInt(skipLines)

    // minimum 1
    if (this.skipLines <= 0) {
      this.skipLines = 1
    }

    if (isNaN(this.skipLines)) {
      throw new Error('Please configure skiplines!')
    }

    this.step()
  }

  step () {
    let blob = this.file.slice(
      this.readPos,
      this.readPos + this.chunkSize + this.skippedBytes
    )

    this.reader.readAsText(blob, this.encoding)
  }
}

/*
  Parse csv headers - stupid split function:)
*/
const parseCsvHeaders = (raw, delimiter = ';') => {
  const result = papaparse.parse(raw, { delimiter })
  return result.data.length > 0 ? result.data[0] : []
}

/*
  Validate profile
  for now - just check keyField exists
*/
const validateProfile = (profile) => {
  // validate key field
  return profile.keyField && profile.bindings[profile.keyField] != null
}

export {
  validateAppConfig,
  FirstLineReader,
  parseCsvHeaders,
  validateProfile
}
