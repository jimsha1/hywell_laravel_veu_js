class CustomValidator {
  required(value) {
    return value !== null && value !== undefined && value.toString().trim() !== '' ? '' : 'This field is required.';
  }

  minLength(value, length) {
    return value.length >= length ? '' : `Minimum length is ${length} characters.`;
  }

  maxLength(value, length) {
    return value.length <= length ? '' : `Maximum length is ${length} characters.`;
  }

  email(value) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(value) ? '' : 'Invalid email address.';
  }
}

window.CustomValidator = new CustomValidator();
