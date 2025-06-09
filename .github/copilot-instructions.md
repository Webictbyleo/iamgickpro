# Copilot Instructions for Modern Web Design Platform

## Project Context
This is a modern web-based design platform (similar to Canva) with the following architecture:
- **Backend**: Symfony 7 (PHP 8.4) with MySQL
- **Frontend**: Vue 3 + TypeScript + Vite + Tailwind CSS + Konva.js
- **Features**: Design editor, dashboard, multi-format export, plugin system, stock media integration

## Backend Development Guidelines (PHP/Symfony)

### PHP Standards & Practices
- Follow PSR-12 coding standards strictly
- Use PHP 8.4 features: enums, readonly properties, union types, match expressions
- Always declare strict types at the top of files
- Use meaningful variable and method names following camelCase
- Keep methods under 20 lines when possible
- Prefer composition over inheritance

### Symfony Framework Patterns
- Use attributes for routing and security instead of annotations
- Implement dependency injection through constructor injection with readonly properties
- Use service classes for business logic, keep controllers thin
- Implement proper error handling with custom exceptions
- Use Doctrine ORM with proper entity relationships and validation constraints
- Implement API endpoints that return JSON responses with proper HTTP status codes
- Use Symfony's serialization groups for API responses
- Implement background job processing with Symfony Messenger for heavy operations like export rendering

### Database Design
- Use proper indexing for search and filter operations
- Implement soft deletes for user data
- Use UUID for public-facing identifiers
- Store design data as JSON with proper validation
- Implement audit trails for important changes

### Security Implementation
- Use JWT tokens for API authentication
- Implement role-based access control
- Validate and sanitize all input data
- Use Symfony's CSRF protection where applicable
- Implement rate limiting for API endpoints
- Secure file upload handling with type validation

## Frontend Development Guidelines (Vue 3/TypeScript)

### Vue 3 Best Practices
- Use Composition API exclusively for all components
- Define TypeScript interfaces for all props, emits, and complex data structures
- Use `<script setup>` syntax for cleaner component structure
- Implement proper lifecycle hooks (onMounted, onUnmounted, etc.)
- Use computed properties for derived state
- Implement watchers for side effects and external API calls
- Use template refs with proper TypeScript typing

### TypeScript Standards
- Define strict interfaces for all data structures
- Use discriminated unions for layer types and other polymorphic data
- Implement proper type guards where necessary
- Use generic types for reusable utilities
- Define enums for constants that have specific values
- Use readonly properties where data shouldn't be mutated

### State Management with Pinia
- Create focused stores for different domains (auth, design, layers, ui)
- Use the composition API style for stores
- Implement proper loading and error states
- Use computed getters for derived state
- Keep actions focused and handle errors appropriately

### Component Architecture
- Create reusable base components for common UI elements
- Use proper prop validation and default values
- Implement proper event emission with TypeScript typing
- Use slots for flexible component composition
- Keep components focused on single responsibilities
- Implement proper loading and error states in data-fetching components

### Canvas Implementation with Konva.js
- Use vue-konva for Vue integration
- Implement proper layer management with Konva Groups
- Use object pooling for performance with many elements
- Implement proper event handling for canvas interactions
- Use Konva's caching for complex shapes
- Implement proper cleanup in component unmount

### Styling with Tailwind CSS
- Use utility classes for consistent spacing and sizing
- Create custom components for repeated patterns
- Use Tailwind's responsive design utilities
- Implement dark mode support where applicable
- Keep custom CSS minimal and use Tailwind's configuration for customization

## API Design Standards

### RESTful Endpoint Structure
- Use consistent naming conventions for endpoints
- Implement proper HTTP methods (GET, POST, PUT, DELETE)
- Use nested resources where relationships exist
- Implement consistent pagination for list endpoints
- Use query parameters for filtering and searching
- Return consistent JSON response formats

### Error Response Format
- Use standard HTTP status codes
- Implement consistent error response structure with error codes and messages
- Provide detailed validation errors for form submissions
- Include request IDs for debugging purposes

## Architecture Patterns

### Editor SDK Design
- Create a central SDK class that manages all editor operations
- Expose clean APIs for layer management, canvas operations, and animation
- Implement proper event system for state changes
- Provide plugin interface for extensibility
- Use command pattern for undo/redo functionality

### Plugin System Architecture
- Use iframe sandboxing for security isolation
- Implement message-based communication between plugin and host
- Create comprehensive plugin API with proper permissions
- Use manifest files for plugin configuration and capabilities
- Implement plugin lifecycle management

### Export and Rendering Pipeline
- Design data should be converted to SVG format for rendering
- Use background job queues for heavy export operations
- Implement progress tracking for long-running exports
- Support multiple output formats (PNG, JPEG, SVG, MP4, GIF)
- Use ImageMagick and Inkscape for format conversion

### File Management System
- Implement secure file upload with proper validation
- Use cloud storage for scalability
- Implement file compression and optimization
- Create thumbnail generation for media files
- Implement file organization and search capabilities

## Performance Considerations

### Frontend Optimization
- Implement virtual scrolling for large lists (templates, media libraries)
- Use lazy loading for images and components
- Debounce user inputs to prevent excessive API calls
- Implement proper caching strategies for API responses
- Use Web Workers for heavy computations
- Optimize bundle size with proper code splitting

### Backend Optimization
- Use database query optimization and proper indexing
- Implement caching layers (Redis) for frequently accessed data
- Use background jobs for time-consuming operations
- Implement proper pagination for large datasets
- Use database migrations for schema changes
- Monitor and optimize slow queries

## Testing Strategy

### Backend Testing
- Write unit tests for service classes and business logic
- Implement integration tests for API endpoints
- Use database fixtures for consistent test data
- Test authentication and authorization scenarios
- Mock external service dependencies

### Frontend Testing
- Write unit tests for components and composables
- Use Vue Testing Library for component testing
- Implement E2E tests for critical user flows
- Test keyboard shortcuts and accessibility
- Mock API responses for consistent testing

## Development Workflow

### Code Organization
- Follow domain-driven design principles
- Separate concerns between layers (controller, service, repository)
- Use consistent naming conventions across the codebase
- Implement proper error handling at each layer
- Create reusable utilities and helpers

### Git Workflow
- Use conventional commit messages
- Create focused commits with single responsibilities
- Use feature branches for new development
- Implement proper code review processes
- Tag releases with semantic versioning

## Security Guidelines

### Data Protection
- Encrypt sensitive data at rest and in transit
- Implement proper access controls for user data
- Use HTTPS for all communications
- Implement proper session management
- Log security-relevant events for auditing

### Input Validation
- Validate all user inputs on both frontend and backend
- Sanitize data before database storage
- Implement proper file upload restrictions
- Use parameterized queries to prevent SQL injection
- Validate file types and sizes for uploads
### General Best Practices:
* **Component-Based Architecture:** Break UI into reusable components.
* **TypeScript:** Use strong typing for props, state, events, and service responses. Define interfaces for all complex objects.
* **Vue 3 Composition API:** Prefer Composition API for logic reuse and organization.
* **Pinia (State Management):** Use Pinia for global state management (user session, editor state if necessary outside SDK).
* **Vite:** Leverage Vite's speed for development and optimized builds.
* **TailwindCSS:** Use utility-first CSS for rapid styling. Keep custom CSS minimal.
* **Konva.js:** Use `vue-konva` for integrating Konva with Vue. Follow Konva best practices for performance (e.g., layer management, caching shapes).
* **Single Responsibility Principle:** Components should have a single responsibility.
* **Async Operations:** Use `async/await` for API calls. Handle loading and error states gracefully.
* **Routing:** Use `vue-router` for navigation. Implement route guards for protected areas.
* **Code Quality:** Use ESLint and Prettier with TypeScript and Vue plugins.
* **Testing:** Write unit tests for components and Composable functions (e.g., using Vitest, Vue Testing Library). E2E tests with Cypress or Playwright.

## Test User credentials
### Pls use this test account for anything test and login ifor protected pages
**Email:** johndoe@example.com
**Password:** Vyhd7Y#PjTb7!TA
### Frontend Development URL
**URL:** http://localhost:3000

# Terminal Output Debugging Guide

## Overview
This guide provides best practices for capturing and interpreting terminal output when debugging PHP/Symfony applications, especially when some commands may not show immediate output or appear to hang.

## Common Terminal Output Issues

### 1. Empty or Missing Output
**Problem**: Terminal commands appear to run but show no output
**Solutions**:
- Add explicit output statements with `echo` or `printf`
- Use `2>&1` to capture both stdout and stderr
- Add verbose flags (`-v`, `--verbose`) when available
- Use `set -x` to show command execution in bash scripts

### 2. Buffered Output
**Problem**: Output appears delayed or all at once
**Solutions**:
- Use `php -u` for unbuffered output
- Add `flush()` calls in PHP scripts
- Use `stdbuf -o0` to disable output buffering
- Add `ob_end_flush()` if output buffering is enabled

### 3. Silent Failures
**Problem**: Commands complete without errors but don't show expected results
**Solutions**:
- Check return codes with `echo $?`
- Add error reporting: `php -d error_reporting=E_ALL -d display_errors=1`
- Use `set -e` in bash scripts to exit on errors
- Add explicit success/failure messages

## Testing Checklist

Before assuming a command failed:

1. ✅ Check return code: `echo $?`
2. ✅ Run with verbose output: add `-v` flags
3. ✅ Capture stderr: use `2>&1`
4. ✅ Add explicit output statements
5. ✅ Check file permissions and syntax
6. ✅ Verify environment and working directory
7. ✅ Test with minimal reproduction case
8. ✅ Check logs: `tail -f var/log/dev.log`