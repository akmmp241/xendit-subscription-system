## XENDIT SUBSCRIPTION SYSTEM
this project is just to acknowledge and validate my understanding of using xendit subscription API. 

this is created using laravel and equipped with authentication system using JWT & refresh token.

## Endpoint

- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/refresh`
- `GET /auth/me`
- `GET /users/payment-methods`
- `POST /users/payment-methods`
- `GET /users/payment-methods/:id`
- `DELETE /users/payment-methods/:id`
- `POST /users/payment-methods/webhook`
- `GET /users/plans`
- `POST /users/plans`
- `PATCH /users/plans/:id`
- `POST /users/plans/:id/deactivate`
- `POST /users/plans/:id/webhook`

## Database Schema
<img src="https://raw.githubusercontent.com/akmmp241/xendit-subscription-system/main/database-schema.png">
