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
  TODO: add support for user defined skip lines parameter
*/
class FirstLineReader {
  constructor () {
    this.events = {}
    this.chunkSize = 512
    this.readPos = 0
    this.reader = new FileReader()
    this.lines = []
    this.chunk = ''
    this.file = null
    this.readedLines = 0

    this.reader.onload = () => {
      this.chunk += this.reader.result
      this.process()
    }
  }

  /*
    Remove non printable characters
  */
  _fixString (str) {
    return str.replace(/\uFFFD/g, '')
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
    if (/\n/.test(this.chunk)) {
      let lines = this.chunk.split('\n')
      let line = this._fixString(lines.shift())
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

  read (file, encoding, skipLines = 0) {
    this.file = file
    this.lines = []
    this.chunk = ''
    this.readPos = 0
    this.encoding = encoding || 'UTF8'
    this.skipLines = parseInt(skipLines)

    this.step()
  }

  step () {
    let blob = this.file.slice(
      this.readPos,
      this.readPos + this.chunkSize
    )

    this.readPos += this.chunkSize

    this.reader.readAsText(blob, this.encoding)
  }
}

/*
  Parse csv headers - stupid split function:)
*/
const parseCsvHeaders = (raw, delimiter = ';') => {
  let clean = raw.replace(/(\r\n|\n)/gm, '')
  clean = clean.split('"').join('')
  return clean.split(delimiter)
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
