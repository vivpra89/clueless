# Pull Request

## Description

Please provide a brief description of the changes in this PR. Include the motivation for these changes and any relevant context.

## Type of Change

Please check the type of change your PR introduces:

- [ ] 🐛 Bug fix (non-breaking change which fixes an issue)
- [ ] ✨ New feature (non-breaking change which adds functionality)
- [ ] 💥 Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] 📝 Documentation update
- [ ] 🎨 Code style update (formatting, renaming)
- [ ] ♻️ Code refactoring (no functional changes, no API changes)
- [ ] ⚡ Performance improvement
- [ ] ✅ Test update
- [ ] 🔧 Configuration change
- [ ] 🏗️ Infrastructure/build change
- [ ] 🔐 Security fix

## Testing Performed

Please describe the tests you've run to verify your changes. Provide instructions so reviewers can reproduce.

- [ ] Unit tests pass locally (`composer test`)
- [ ] Frontend builds successfully (`npm run build`)
- [ ] Code follows project style guidelines (`php artisan pint` and `npm run format`)
- [ ] Manual testing completed

### Test Configuration
- **PHP Version:**
- **Node Version:**
- **Browser (if applicable):**
- **Operating System:**

### Manual Testing Steps
1. 
2. 
3. 

## Checklist

Please review the following items before submitting your PR:

### Code Quality
- [ ] My code follows the project's code style and conventions
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings or errors
- [ ] TypeScript types are properly defined (no `any` types unless absolutely necessary)

### Database Changes (if applicable)
- [ ] I have created necessary migrations
- [ ] Migrations have been tested on both databases (`database.sqlite` and `nativephp.sqlite`)
- [ ] Database schema changes are backward compatible

### Frontend Changes (if applicable)
- [ ] Component changes follow Vue 3 Composition API patterns
- [ ] UI components maintain consistency with existing design system
- [ ] Changes are responsive and work on different screen sizes
- [ ] Dark mode compatibility has been verified

### Testing
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally
- [ ] Any dependent changes have been merged and published

## Related Issues

Please link any related issues here using the format `Fixes #issue_number` or `Relates to #issue_number`

- Fixes #
- Relates to #

## Screenshots (if applicable)

If your changes include UI updates, please add screenshots showing:

### Before
<!-- Add screenshots of the current behavior -->

### After
<!-- Add screenshots showing your changes -->

### Dark Mode (if UI changes)
<!-- Add screenshots showing dark mode compatibility -->

## Additional Notes

Add any additional notes, concerns, or discussion points for reviewers.

## Reviewer Checklist

For reviewers to complete:

- [ ] Code follows project conventions and style guide
- [ ] Changes are well-documented and easy to understand
- [ ] Tests adequately cover the changes
- [ ] No security vulnerabilities introduced
- [ ] Performance impact is acceptable
- [ ] UI changes are consistent with design system
- [ ] Database migrations are safe and reversible