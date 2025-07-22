# Exemplo de Integração Android - Onlifin API

## Configuração Inicial

### 1. Dependências (build.gradle)

```kotlin
dependencies {
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    implementation 'com.squareup.okhttp3:logging-interceptor:4.11.0'
    implementation 'androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0'
    implementation 'androidx.lifecycle:lifecycle-livedata-ktx:2.7.0'
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3'
}
```

### 2. Permissões (AndroidManifest.xml)

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
```

## Modelos de Dados

### User.kt
```kotlin
data class User(
    val id: Int,
    val name: String,
    val email: String,
    val phone: String?,
    val profilePhotoUrl: String,
    val isAdmin: Boolean,
    val isActive: Boolean,
    val notifications: NotificationSettings,
    val security: SecuritySettings,
    val socialAuth: SocialAuthSettings,
    val createdAt: String,
    val updatedAt: String
)

data class NotificationSettings(
    val emailNotifications: Boolean,
    val whatsappNotifications: Boolean,
    val pushNotifications: Boolean,
    val dueDateNotifications: Boolean
)

data class SecuritySettings(
    val twoFactorEnabled: Boolean,
    val twoFactorConfirmedAt: String?
)

data class SocialAuthSettings(
    val googleConnected: Boolean,
    val googleAvatar: String?
)
```

### Transaction.kt
```kotlin
data class Transaction(
    val id: Int,
    val type: String, // "income" ou "expense"
    val status: String, // "paid" ou "pending"
    val date: String,
    val description: String,
    val amount: Double,
    val amountFormatted: String,
    val notes: String?,
    val cliente: String?,
    val fornecedor: String?,
    val recurrence: RecurrenceInfo,
    val category: Category?,
    val account: Account?,
    val createdAt: String,
    val updatedAt: String
)

data class RecurrenceInfo(
    val type: String?,
    val period: Int?,
    val installmentNumber: Int?,
    val totalInstallments: Int?,
    val nextDate: String?
)
```

### Account.kt
```kotlin
data class Account(
    val id: Int,
    val name: String,
    val type: String,
    val typeLabel: String,
    val initialBalance: Double,
    val currentBalance: Double,
    val currentBalanceFormatted: String,
    val description: String?,
    val color: String,
    val active: Boolean,
    val transactionsCount: Int?,
    val createdAt: String,
    val updatedAt: String
)
```

### Category.kt
```kotlin
data class Category(
    val id: Int,
    val name: String,
    val type: String,
    val color: String,
    val icon: String,
    val description: String?,
    val statistics: CategoryStatistics?,
    val createdAt: String,
    val updatedAt: String
)

data class CategoryStatistics(
    val transactionsCount: Int,
    val totalAmount: Double,
    val totalAmountFormatted: String,
    val lastUsed: String?
)
```

## Respostas da API

### ApiResponse.kt
```kotlin
data class ApiResponse<T>(
    val success: Boolean,
    val data: T? = null,
    val message: String? = null,
    val errors: Map<String, List<String>>? = null,
    val timestamp: String
)

data class PaginatedResponse<T>(
    val success: Boolean,
    val data: PaginatedData<T>,
    val timestamp: String
)

data class PaginatedData<T>(
    val transactions: List<T>? = null,
    val accounts: List<T>? = null,
    val categories: List<T>? = null,
    val pagination: Pagination
)

data class Pagination(
    val currentPage: Int,
    val lastPage: Int,
    val perPage: Int,
    val total: Int,
    val from: Int,
    val to: Int
)
```

## Interface da API

### OnlifinApiService.kt
```kotlin
import retrofit2.Response
import retrofit2.http.*

interface OnlifinApiService {
    
    // Autenticação
    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<LoginResponse>>
    
    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse<LoginResponse>>
    
    @POST("auth/logout")
    suspend fun logout(): Response<ApiResponse<Unit>>
    
    @GET("auth/me")
    suspend fun getProfile(): Response<ApiResponse<UserResponse>>
    
    @POST("auth/refresh")
    suspend fun refreshToken(@Body request: RefreshTokenRequest): Response<ApiResponse<TokenResponse>>
    
    // Transações
    @GET("transactions")
    suspend fun getTransactions(
        @Query("type") type: String? = null,
        @Query("status") status: String? = null,
        @Query("account_id") accountId: Int? = null,
        @Query("category_id") categoryId: Int? = null,
        @Query("date_from") dateFrom: String? = null,
        @Query("date_to") dateTo: String? = null,
        @Query("search") search: String? = null,
        @Query("per_page") perPage: Int = 15,
        @Query("page") page: Int = 1
    ): Response<PaginatedResponse<Transaction>>
    
    @POST("transactions")
    suspend fun createTransaction(@Body request: CreateTransactionRequest): Response<ApiResponse<TransactionResponse>>
    
    @GET("transactions/{id}")
    suspend fun getTransaction(@Path("id") id: Int): Response<ApiResponse<TransactionResponse>>
    
    @PUT("transactions/{id}")
    suspend fun updateTransaction(@Path("id") id: Int, @Body request: UpdateTransactionRequest): Response<ApiResponse<TransactionResponse>>
    
    @DELETE("transactions/{id}")
    suspend fun deleteTransaction(@Path("id") id: Int): Response<ApiResponse<Unit>>
    
    @GET("transactions/summary")
    suspend fun getTransactionsSummary(
        @Query("date_from") dateFrom: String? = null,
        @Query("date_to") dateTo: String? = null,
        @Query("account_id") accountId: Int? = null
    ): Response<ApiResponse<TransactionSummary>>
    
    // Contas
    @GET("accounts")
    suspend fun getAccounts(
        @Query("active") active: Boolean? = null,
        @Query("type") type: String? = null
    ): Response<ApiResponse<AccountsResponse>>
    
    @POST("accounts")
    suspend fun createAccount(@Body request: CreateAccountRequest): Response<ApiResponse<AccountResponse>>
    
    @GET("accounts/{id}")
    suspend fun getAccount(@Path("id") id: Int): Response<ApiResponse<AccountResponse>>
    
    @PUT("accounts/{id}")
    suspend fun updateAccount(@Path("id") id: Int, @Body request: UpdateAccountRequest): Response<ApiResponse<AccountResponse>>
    
    @DELETE("accounts/{id}")
    suspend fun deleteAccount(@Path("id") id: Int): Response<ApiResponse<Unit>>
    
    // Categorias
    @GET("categories")
    suspend fun getCategories(
        @Query("type") type: String? = null,
        @Query("with_stats") withStats: Boolean = false
    ): Response<ApiResponse<CategoriesResponse>>
    
    @POST("categories")
    suspend fun createCategory(@Body request: CreateCategoryRequest): Response<ApiResponse<CategoryResponse>>
    
    // Relatórios
    @GET("reports/dashboard")
    suspend fun getDashboard(): Response<ApiResponse<DashboardResponse>>
    
    @GET("reports/cash-flow")
    suspend fun getCashFlow(
        @Query("date_from") dateFrom: String,
        @Query("date_to") dateTo: String,
        @Query("group_by") groupBy: String = "month",
        @Query("account_id") accountId: Int? = null
    ): Response<ApiResponse<CashFlowResponse>>
    
    // IA
    @POST("ai/chat")
    suspend fun chatWithAI(@Body request: ChatRequest): Response<ApiResponse<ChatResponse>>
    
    @POST("ai/analysis")
    suspend fun getFinancialAnalysis(@Body request: AnalysisRequest): Response<ApiResponse<AnalysisResponse>>
    
    @GET("ai/insights")
    suspend fun getInsights(): Response<ApiResponse<InsightsResponse>>
}
```

## Configuração do Retrofit

### NetworkModule.kt
```kotlin
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object NetworkModule {
    
    private const val BASE_URL = "http://172.20.120.180:8080/api/"
    
    private fun createAuthInterceptor(tokenProvider: () -> String?): Interceptor {
        return Interceptor { chain ->
            val originalRequest = chain.request()
            val token = tokenProvider()
            
            val newRequest = if (token != null) {
                originalRequest.newBuilder()
                    .header("Authorization", "Bearer $token")
                    .header("Accept", "application/json")
                    .header("Content-Type", "application/json")
                    .header("User-Agent", "OnlifinAndroid/1.0")
                    .build()
            } else {
                originalRequest.newBuilder()
                    .header("Accept", "application/json")
                    .header("Content-Type", "application/json")
                    .header("User-Agent", "OnlifinAndroid/1.0")
                    .build()
            }
            
            chain.proceed(newRequest)
        }
    }
    
    fun createApiService(tokenProvider: () -> String?): OnlifinApiService {
        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }
        
        val client = OkHttpClient.Builder()
            .addInterceptor(createAuthInterceptor(tokenProvider))
            .addInterceptor(loggingInterceptor)
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()
        
        val retrofit = Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
        
        return retrofit.create(OnlifinApiService::class.java)
    }
}
```

## Repository

### OnlifinRepository.kt
```kotlin
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class OnlifinRepository(
    private val apiService: OnlifinApiService,
    private val tokenManager: TokenManager
) {
    
    suspend fun login(email: String, password: String, deviceName: String): Result<LoginResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val request = LoginRequest(email, password, deviceName)
                val response = apiService.login(request)
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val loginResponse = response.body()!!.data!!
                    tokenManager.saveToken(loginResponse.token)
                    Result.success(loginResponse)
                } else {
                    val errorMessage = response.body()?.message ?: "Erro no login"
                    Result.failure(Exception(errorMessage))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }
    
    suspend fun getTransactions(
        type: String? = null,
        status: String? = null,
        page: Int = 1,
        perPage: Int = 15
    ): Result<PaginatedData<Transaction>> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.getTransactions(
                    type = type,
                    status = status,
                    page = page,
                    perPage = perPage
                )
                
                if (response.isSuccessful && response.body()?.success == true) {
                    Result.success(response.body()!!.data)
                } else {
                    val errorMessage = response.body()?.message ?: "Erro ao carregar transações"
                    Result.failure(Exception(errorMessage))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }
    
    suspend fun createTransaction(
        type: String,
        status: String,
        date: String,
        description: String,
        amount: Double,
        categoryId: Int,
        accountId: Int,
        notes: String? = null
    ): Result<Transaction> {
        return withContext(Dispatchers.IO) {
            try {
                val request = CreateTransactionRequest(
                    type, status, date, description, amount, categoryId, accountId, notes
                )
                val response = apiService.createTransaction(request)
                
                if (response.isSuccessful && response.body()?.success == true) {
                    Result.success(response.body()!!.data!!.transaction)
                } else {
                    val errorMessage = response.body()?.message ?: "Erro ao criar transação"
                    Result.failure(Exception(errorMessage))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }
    
    // Implementar outros métodos conforme necessário...
}
```

## Gerenciamento de Token

### TokenManager.kt
```kotlin
import android.content.Context
import android.content.SharedPreferences

class TokenManager(context: Context) {
    
    private val prefs: SharedPreferences = context.getSharedPreferences(
        "onlifin_prefs", Context.MODE_PRIVATE
    )
    
    fun saveToken(token: String) {
        prefs.edit().putString("auth_token", token).apply()
    }
    
    fun getToken(): String? {
        return prefs.getString("auth_token", null)
    }
    
    fun clearToken() {
        prefs.edit().remove("auth_token").apply()
    }
    
    fun isLoggedIn(): Boolean {
        return getToken() != null
    }
}
```

## ViewModel Exemplo

### TransactionsViewModel.kt
```kotlin
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class TransactionsViewModel(
    private val repository: OnlifinRepository
) : ViewModel() {
    
    private val _transactions = MutableStateFlow<List<Transaction>>(emptyList())
    val transactions: StateFlow<List<Transaction>> = _transactions
    
    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading
    
    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error
    
    fun loadTransactions(type: String? = null, status: String? = null) {
        viewModelScope.launch {
            _isLoading.value = true
            _error.value = null
            
            repository.getTransactions(type, status)
                .onSuccess { data ->
                    _transactions.value = data.transactions ?: emptyList()
                }
                .onFailure { exception ->
                    _error.value = exception.message
                }
            
            _isLoading.value = false
        }
    }
    
    fun createTransaction(
        type: String,
        status: String,
        date: String,
        description: String,
        amount: Double,
        categoryId: Int,
        accountId: Int,
        notes: String? = null
    ) {
        viewModelScope.launch {
            _isLoading.value = true
            _error.value = null
            
            repository.createTransaction(
                type, status, date, description, amount, categoryId, accountId, notes
            )
                .onSuccess {
                    // Recarregar lista após criar
                    loadTransactions()
                }
                .onFailure { exception ->
                    _error.value = exception.message
                }
            
            _isLoading.value = false
        }
    }
}
```

## Tratamento de Erros

### ApiErrorHandler.kt
```kotlin
import retrofit2.Response

object ApiErrorHandler {
    
    fun <T> handleApiError(response: Response<ApiResponse<T>>): String {
        return when (response.code()) {
            401 -> "Sessão expirada. Faça login novamente."
            403 -> "Acesso negado."
            404 -> "Recurso não encontrado."
            422 -> {
                val errors = response.body()?.errors
                if (errors != null) {
                    errors.values.flatten().joinToString("\n")
                } else {
                    "Dados inválidos."
                }
            }
            429 -> "Muitas tentativas. Tente novamente em alguns minutos."
            500 -> "Erro interno do servidor. Tente novamente mais tarde."
            else -> response.body()?.message ?: "Erro desconhecido."
        }
    }
}
```

## Uso em Activity/Fragment

### MainActivity.kt
```kotlin
class MainActivity : AppCompatActivity() {
    
    private lateinit var viewModel: TransactionsViewModel
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // Configurar ViewModel
        val tokenManager = TokenManager(this)
        val apiService = NetworkModule.createApiService { tokenManager.getToken() }
        val repository = OnlifinRepository(apiService, tokenManager)
        viewModel = TransactionsViewModel(repository)
        
        // Observar dados
        lifecycleScope.launch {
            viewModel.transactions.collect { transactions ->
                // Atualizar UI com transações
                updateTransactionsList(transactions)
            }
        }
        
        lifecycleScope.launch {
            viewModel.isLoading.collect { isLoading ->
                // Mostrar/esconder loading
                showLoading(isLoading)
            }
        }
        
        lifecycleScope.launch {
            viewModel.error.collect { error ->
                error?.let {
                    // Mostrar erro
                    showError(it)
                }
            }
        }
        
        // Carregar transações
        viewModel.loadTransactions()
    }
    
    private fun updateTransactionsList(transactions: List<Transaction>) {
        // Implementar atualização da lista
    }
    
    private fun showLoading(isLoading: Boolean) {
        // Implementar indicador de loading
    }
    
    private fun showError(error: String) {
        // Implementar exibição de erro
    }
}
```

Este exemplo fornece uma base sólida para integração com a API Onlifin no Android, incluindo autenticação, gerenciamento de estado, tratamento de erros e padrões recomendados para desenvolvimento Android moderno.
