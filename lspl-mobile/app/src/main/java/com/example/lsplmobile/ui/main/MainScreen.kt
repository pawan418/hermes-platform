package com.example.lsplmobile.ui.main

import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation3.runtime.NavKey
import com.example.lsplmobile.WebViewScreen
import com.example.lsplmobile.data.*
import com.example.lsplmobile.theme.*
import androidx.compose.material3.TabRowDefaults.tabIndicatorOffset
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.text.HtmlCompat
import android.text.method.LinkMovementMethod
import android.widget.TextView

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainScreen(
    onItemClick: (NavKey) -> Unit,
    modifier: Modifier = Modifier,
    viewModel: MainScreenViewModel = viewModel { MainScreenViewModel(DefaultDataRepository()) }
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    var selectedTab by remember { mutableIntStateOf(0) }

    // Lifted Detail Dialog States
    var activeDetailService by remember { mutableStateOf<Pair<ServiceItem, String>?>(null) }
    var activeDetailIndustry by remember { mutableStateOf<Pair<IndustryItem, String>?>(null) }

    // Lifted Estimator Form States (so we can pre-fill from detail cards)
    var estPortal by remember { mutableStateOf("enterprise") }
    var estServiceSelected by remember { mutableStateOf("") }
    var estName by remember { mutableStateOf("") }
    var estEmail by remember { mutableStateOf("") }
    var estPhone by remember { mutableStateOf("") }
    var estMessage by remember { mutableStateOf("") }

    // Multi-step Selector States for Pricing Calculator
    var estStep by remember { mutableIntStateOf(1) }
    var estScaleSelected by remember { mutableStateOf("startup") }
    var estTimelineSelected by remember { mutableStateOf("standard") }
    var estCurrencySelected by remember { mutableStateOf("INR") }

    // Auto-detect currency on first load based on timezone or country
    LaunchedEffect(Unit) {
        try {
            // 1. Try IP Geolocation first
            val detectedCountry = ApiClient.detectCountry()
            if (detectedCountry != null) {
                estCurrencySelected = when (detectedCountry) {
                    "GB" -> "GBP"
                    "US" -> "USD"
                    "IN" -> "INR"
                    "CA", "AU" -> "USD"
                    "AT", "BE", "CY", "EE", "FI", "FR", "DE", "GR", "IE", "IT", "LV", "LT", "LU", "MT", "NL", "PT", "SK", "SI", "ES" -> "EUR"
                    else -> "INR"
                }
            } else {
                // 2. Fallback to System Locale & Timezone ID
                val localeCountry = java.util.Locale.getDefault().country.uppercase()
                val tzId = java.util.TimeZone.getDefault().id.uppercase()
                estCurrencySelected = when {
                    localeCountry == "GB" || tzId.contains("LONDON") || tzId.contains("EUROPE/LONDON") || tzId.contains("GMT") -> "GBP"
                    localeCountry == "US" || tzId.contains("AMERICA") || tzId.contains("US/") -> "USD"
                    localeCountry == "IN" || tzId.contains("KOLKATA") || tzId.contains("CALCUTTA") || tzId.contains("ASIA/KOLKATA") || tzId.contains("ASIA/CALCUTTA") -> "INR"
                    localeCountry in listOf("AT", "BE", "CY", "EE", "FI", "FR", "DE", "GR", "IE", "IT", "LV", "LT", "LU", "MT", "NL", "PT", "SK", "SI", "ES") ||
                            (tzId.contains("EUROPE") && !tzId.contains("LONDON")) -> "EUR"
                    else -> "INR"
                }
            }
        } catch (e: Exception) {
            estCurrencySelected = "INR"
        }
    }

    when (state) {
        MainScreenUiState.Loading -> {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(ObsidianBg),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = NeonCyan)
            }
        }
        is MainScreenUiState.Success -> {
            val apiData = (state as MainScreenUiState.Success).data

            // Pre-fill first service title if empty
            if (estServiceSelected.isEmpty()) {
                val list = when (estPortal) {
                    "enterprise" -> apiData.enterprise.services
                    "academy" -> apiData.academy.services
                    else -> apiData.ai.services
                }
                estServiceSelected = list.firstOrNull()?.title ?: ""
            }

            Scaffold(
                containerColor = ObsidianBg,
                topBar = {
                    Column {
                        TopAppBar(
                            title = {
                                Text(
                                    text = when (selectedTab) {
                                        0 -> "LSPL HUB"
                                        1 -> "IT SOLUTIONS"
                                        2 -> "LSPL ACADEMY"
                                        3 -> "AI RESEARCH LAB"
                                        else -> "PROJECT ESTIMATOR"
                                    },
                                    fontWeight = FontWeight.ExtraBold,
                                    fontSize = 18.sp,
                                    letterSpacing = 1.sp,
                                    color = TextPrimary
                                )
                            },
                            colors = TopAppBarDefaults.topAppBarColors(
                                containerColor = ObsidianBg,
                                titleContentColor = TextPrimary
                            ),
                            actions = {
                                IconButton(onClick = { viewModel.refreshData() }) {
                                    Icon(Icons.Default.Refresh, contentDescription = "Refresh", tint = NeonCyan)
                                }
                            }
                        )
                        // Neon divider
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(1.dp)
                                .background(
                                    Brush.horizontalGradient(
                                        colors = listOf(NeonPurple, NeonCyan, NeonEmerald)
                                    )
                                )
                        )
                    }
                },
                bottomBar = {
                    CustomFloatingNavBar(
                        selectedTab = selectedTab,
                        onTabSelected = { selectedTab = it }
                    )
                }
            ) { paddingValues ->
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(paddingValues)
                        .background(ObsidianBg)
                ) {
                    when (selectedTab) {
                        0 -> HubDashboard(
                            data = apiData,
                            onNavigateToTab = { selectedTab = it },
                            onServiceClick = { service, portal -> activeDetailService = service to portal },
                            onItemClick = onItemClick
                        )
                        1 -> PortalScreen(
                            portalKey = "enterprise",
                            portalData = apiData.enterprise,
                            onServiceClick = { service -> activeDetailService = service to "enterprise" },
                            onIndustryClick = { industry -> activeDetailIndustry = industry to "enterprise" }
                        )
                        2 -> PortalScreen(
                            portalKey = "academy",
                            portalData = apiData.academy,
                            onServiceClick = { service -> activeDetailService = service to "academy" },
                            onIndustryClick = {}
                        )
                        3 -> PortalScreen(
                            portalKey = "ai",
                            portalData = apiData.ai,
                            onServiceClick = { service -> activeDetailService = service to "ai" },
                            onIndustryClick = { industry -> activeDetailIndustry = industry to "ai" }
                        )
                        4 -> LeadFormScreen(
                            viewModel = viewModel,
                            data = apiData,
                            portal = estPortal,
                            onPortalChange = { newPortal ->
                                estPortal = newPortal
                                val list = when (newPortal) {
                                    "enterprise" -> apiData.enterprise.services
                                    "academy" -> apiData.academy.services
                                    else -> apiData.ai.services
                                }
                                estServiceSelected = list.firstOrNull()?.title ?: ""
                                if (newPortal == "academy") {
                                    estScaleSelected = "offline"
                                    estTimelineSelected = "regular"
                                } else {
                                    estScaleSelected = "startup"
                                    estTimelineSelected = "standard"
                                }
                            },
                            serviceSelected = estServiceSelected,
                            onServiceChange = { estServiceSelected = it },
                            name = estName,
                            onNameChange = { estName = it },
                            email = estEmail,
                            onEmailChange = { estEmail = it },
                            phone = estPhone,
                            onPhoneChange = { estPhone = it },
                            message = estMessage,
                            onMessageChange = { estMessage = it },
                            estStep = estStep,
                            onStepChange = { estStep = it },
                            scaleSelected = estScaleSelected,
                            onScaleChange = { estScaleSelected = it },
                            timelineSelected = estTimelineSelected,
                            onTimelineChange = { estTimelineSelected = it },
                            currencySelected = estCurrencySelected,
                            onCurrencyChange = { estCurrencySelected = it }
                        )
                    }

                    // Render dialogs if active
                    activeDetailService?.let { (service, portal) ->
                        ServiceDetailDialog(
                            service = service,
                            portalKey = portal,
                            onDismiss = { activeDetailService = null },
                            onGetEstimate = { serviceName ->
                                estPortal = portal
                                estServiceSelected = serviceName
                                if (portal == "academy") {
                                    estScaleSelected = "offline"
                                    estTimelineSelected = "regular"
                                } else {
                                    estScaleSelected = "startup"
                                    estTimelineSelected = "standard"
                                }
                                selectedTab = 4 // Switch to Estimator
                                estStep = 1 // Reset to Step 1
                                activeDetailService = null // Close dialog
                            }
                        )
                    }

                    activeDetailIndustry?.let { (industry, portal) ->
                        IndustryDetailDialog(
                            industry = industry,
                            portalKey = portal,
                            onDismiss = { activeDetailIndustry = null },
                            onGetEstimate = { industryName ->
                                estPortal = portal
                                estServiceSelected = industryName
                                if (portal == "academy") {
                                    estScaleSelected = "offline"
                                    estTimelineSelected = "regular"
                                } else {
                                    estScaleSelected = "startup"
                                    estTimelineSelected = "standard"
                                }
                                selectedTab = 4 // Switch to Estimator
                                estStep = 1 // Reset to Step 1
                                activeDetailIndustry = null // Close dialog
                            }
                        )
                    }
                }
            }
        }
        is MainScreenUiState.Error -> {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(ObsidianBg)
                    .padding(24.dp),
                contentAlignment = Alignment.Center
            ) {
                Card(
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = CardDarkBg),
                    border = BorderStroke(1.dp, CardBorder),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(
                        modifier = Modifier.padding(24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(
                            imageVector = Icons.Default.Warning,
                            contentDescription = null,
                            tint = GlowRed,
                            modifier = Modifier.size(48.dp)
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        Text(
                            text = "Connection Failed",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimary
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "Could not sync data with local servers.\n\nVerify PHP server is running on:\n${ApiClient.baseUrl}",
                            color = TextSecondary,
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Medium
                        )
                        Spacer(modifier = Modifier.height(24.dp))
                        Button(
                            onClick = { viewModel.loadData() },
                            colors = ButtonDefaults.buttonColors(containerColor = NeonCyan),
                            shape = RoundedCornerShape(10.dp)
                        ) {
                            Text("Retry Connection", color = ObsidianBg, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun CustomFloatingNavBar(
    selectedTab: Int,
    onTabSelected: (Int) -> Unit
) {
    val items = listOf(
        Pair("Hub", Icons.Default.Home),
        Pair("IT Solutions", Icons.Default.ShoppingCart),
        Pair("Academy", Icons.Default.List),
        Pair("AI Lab", Icons.Default.Star),
        Pair("Estimator", Icons.Default.Email)
    )

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 12.dp),
        shape = RoundedCornerShape(24.dp),
        colors = CardDefaults.cardColors(containerColor = CardDarkBg.copy(alpha = 0.94f)),
        border = BorderStroke(1.dp, CardBorder),
        elevation = CardDefaults.cardElevation(8.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 8.dp, vertical = 8.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            items.forEachIndexed { index, (label, icon) ->
                val isSelected = selectedTab == index
                val activeColor = when (index) {
                    0 -> NeonCyan
                    1 -> NeonPurple
                    2 -> NeonIndigo
                    3 -> NeonEmerald
                    else -> NeonCyan
                }
                val tint = if (isSelected) activeColor else TextSecondary

                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    modifier = Modifier
                        .weight(1f)
                        .clip(RoundedCornerShape(16.dp))
                        .clickable { onTabSelected(index) }
                        .padding(vertical = 8.dp)
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = label,
                        tint = tint,
                        modifier = Modifier.size(22.dp)
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = label,
                        color = tint,
                        fontSize = 10.sp,
                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    if (isSelected) {
                        Spacer(modifier = Modifier.height(4.dp))
                        Box(
                            modifier = Modifier
                                .size(width = 16.dp, height = 3.dp)
                                .background(activeColor, RoundedCornerShape(1.5.dp))
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun NetworkImage(url: String, contentDescription: String?, modifier: Modifier = Modifier) {
    var bitmap by remember(url) { mutableStateOf<android.graphics.Bitmap?>(null) }
    LaunchedEffect(url) {
        withContext(Dispatchers.IO) {
            try {
                val connection = java.net.URL(url).openConnection() as java.net.HttpURLConnection
                connection.connectTimeout = 5000
                connection.readTimeout = 5000
                connection.doInput = true
                connection.connect()
                val input = connection.inputStream
                bitmap = android.graphics.BitmapFactory.decodeStream(input)
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }
    if (bitmap != null) {
        androidx.compose.foundation.Image(
            bitmap = bitmap!!.asImageBitmap(),
            contentDescription = contentDescription,
            modifier = modifier,
            contentScale = ContentScale.Crop
        )
    } else {
        Box(
            modifier = modifier.background(SurfaceLight),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = Icons.Default.Star,
                contentDescription = null,
                tint = TextMuted
            )
        }
    }
}

@Composable
fun HubDashboard(
    data: ApiResponse,
    onNavigateToTab: (Int) -> Unit,
    onServiceClick: (ServiceItem, String) -> Unit,
    onItemClick: (NavKey) -> Unit
) {
    val scrollState = rememberScrollState()
    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(scrollState)
            .padding(16.dp)
    ) {
        // Welcome Hero Banner
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .clip(RoundedCornerShape(20.dp))
                .background(
                    Brush.linearGradient(
                        colors = listOf(NeonIndigo, NeonCyan, NeonEmerald)
                    )
                )
                .padding(24.dp)
        ) {
            Column {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(32.dp)
                            .background(Color.White.copy(alpha = 0.2f), RoundedCornerShape(8.dp)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.Star,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(18.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = "LSPL SYSTEMS HUB",
                        color = Color.White,
                        fontWeight = FontWeight.ExtraBold,
                        fontSize = 11.sp,
                        letterSpacing = 1.5.sp
                    )
                }
                Spacer(modifier = Modifier.height(12.dp))
                Text(
                    text = "Future-Proof Digital Ecosystem",
                    color = Color.White,
                    fontWeight = FontWeight.ExtraBold,
                    fontSize = 24.sp,
                    lineHeight = 30.sp
                )
                Spacer(modifier = Modifier.height(6.dp))
                Text(
                    text = "Access Enterprise Software, Professional Bootcamps, and Artificial Intelligence Research Lab in one unified native workspace.",
                    color = Color.White.copy(alpha = 0.85f),
                    fontSize = 13.sp,
                    lineHeight = 18.sp
                )
            }
        }

        Spacer(modifier = Modifier.height(28.dp))

        // Divisions Header
        Text(
            text = "DIGITAL DEPARTMENTS",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.ExtraBold,
            color = TextPrimary,
            letterSpacing = 1.sp
        )
        Spacer(modifier = Modifier.height(12.dp))

        // Enterprise Card
        PortalHubCard(
            title = "LSPL Enterprise",
            tagline = "IT Services, Custom ERP & Enterprise Dev",
            accentColor = NeonPurple,
            icon = Icons.Default.Build,
            itemCount = data.enterprise.services.size + data.enterprise.industries.size,
            onClick = { onNavigateToTab(1) }
        )

        Spacer(modifier = Modifier.height(12.dp))

        // Academy Card
        PortalHubCard(
            title = "LSPL Academy",
            tagline = "Professional Coding & Cybersecurity Training",
            accentColor = NeonIndigo,
            icon = Icons.Default.List,
            itemCount = data.academy.services.size,
            onClick = { onNavigateToTab(2) }
        )

        Spacer(modifier = Modifier.height(12.dp))

        // AI Lab Card
        PortalHubCard(
            title = "LSPL AI Research Lab",
            tagline = "Custom RAGs, Deep Learning & LLM Services",
            accentColor = NeonEmerald,
            icon = Icons.Default.Star,
            itemCount = data.ai.services.size + data.ai.industries.size,
            onClick = { onNavigateToTab(3) }
        )

        // Latest Blogs Header
        val allBlogs = remember(data) {
            val list = mutableListOf<Pair<String, BlogItem>>()
            data.enterprise.blogs.take(3).forEach { list.add("enterprise" to it) }
            data.academy.blogs.take(3).forEach { list.add("academy" to it) }
            data.ai.blogs.take(3).forEach { list.add("ai" to it) }
            list.sortByDescending { it.second.created_at }
            list
        }

        if (allBlogs.isNotEmpty()) {
            Spacer(modifier = Modifier.height(28.dp))
            Text(
                text = "LATEST PUBLICATIONS",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.ExtraBold,
                color = TextPrimary,
                letterSpacing = 1.sp
            )
            Spacer(modifier = Modifier.height(12.dp))

            LazyRow(
                horizontalArrangement = Arrangement.spacedBy(16.dp),
                contentPadding = PaddingValues(bottom = 16.dp)
            ) {
                items(allBlogs) { (portalKey, blog) ->
                    val blogUrl = remember(blog.slug) {
                        val path = when (portalKey) {
                            "enterprise" -> "longwaysoftronix_v2/blog/${blog.slug}"
                            "academy" -> "lspl.xyz_v2/blog/${blog.slug}"
                            else -> "lsxpl_v2/blog/${blog.slug}"
                        }
                        "${ApiClient.baseUrl.trimEnd('/')}/$path"
                    }

                    Card(
                        modifier = Modifier
                            .width(280.dp)
                            .clickable {
                                onItemClick(WebViewScreen(url = blogUrl, title = blog.title))
                            },
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = CardDarkBg),
                        border = BorderStroke(1.dp, CardBorder),
                        elevation = CardDefaults.cardElevation(4.dp)
                    ) {
                        Column {
                            // Image container
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(140.dp)
                                    .background(SurfaceLight)
                            ) {
                                val imageUrl = remember(blog.image_url) {
                                    if (blog.image_url.isNullOrEmpty()) ""
                                    else {
                                        val portalFolder = when (portalKey) {
                                            "enterprise" -> "longwaysoftronix_v2"
                                            "academy" -> "lspl.xyz_v2"
                                            else -> "lsxpl_v2"
                                        }
                                        "${ApiClient.baseUrl.trimEnd('/')}/$portalFolder/${blog.image_url}"
                                    }
                                }
                                if (imageUrl.isNotEmpty()) {
                                    NetworkImage(
                                        url = imageUrl,
                                        contentDescription = blog.title,
                                        modifier = Modifier.fillMaxSize()
                                    )
                                } else {
                                    Box(
                                        modifier = Modifier
                                            .fillMaxSize()
                                            .background(
                                                Brush.linearGradient(
                                                    colors = listOf(CardDarkBg, CardBorder)
                                                )
                                            ),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Icon(
                                            imageVector = Icons.Default.Star,
                                            contentDescription = null,
                                            tint = TextMuted,
                                            modifier = Modifier.size(36.dp)
                                        )
                                    }
                                }

                                // Portal category tag on top of image
                                Box(
                                    modifier = Modifier
                                        .align(Alignment.TopStart)
                                        .padding(12.dp)
                                        .background(
                                            color = when (portalKey) {
                                                "enterprise" -> NeonPurple
                                                "academy" -> NeonIndigo
                                                else -> NeonEmerald
                                            },
                                            shape = RoundedCornerShape(6.dp)
                                        )
                                        .padding(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text(
                                        text = portalKey.uppercase(),
                                        fontSize = 10.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = ObsidianBg
                                    )
                                }
                            }

                            // Content details
                            Column(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp)
                            ) {
                                Text(
                                    text = blog.created_at.substringBefore(" "),
                                    fontSize = 11.sp,
                                    color = TextSecondary,
                                    fontWeight = FontWeight.Medium
                                )
                                Spacer(modifier = Modifier.height(6.dp))
                                Text(
                                    text = blog.title,
                                    style = MaterialTheme.typography.bodyLarge,
                                    fontWeight = FontWeight.Bold,
                                    color = TextPrimary,
                                    maxLines = 2,
                                    overflow = TextOverflow.Ellipsis
                                )
                            }
                        }
                    }
                }
            }
        }
        Spacer(modifier = Modifier.height(30.dp)) // Padding at bottom for floating nav
    }
}

@Composable
fun PortalHubCard(
    title: String,
    tagline: String,
    accentColor: Color,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    itemCount: Int,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = CardDarkBg),
        border = BorderStroke(1.dp, CardBorder),
        elevation = CardDefaults.cardElevation(2.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .height(IntrinsicSize.Max),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Neon accent left vertical bar
            Box(
                modifier = Modifier
                    .width(4.dp)
                    .fillMaxHeight()
                    .background(accentColor)
            )
            Row(
                modifier = Modifier
                    .weight(1f)
                    .padding(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Icon circle
                Box(
                    modifier = Modifier
                        .size(46.dp)
                        .background(accentColor.copy(alpha = 0.12f), CircleShape)
                        .border(1.dp, accentColor.copy(alpha = 0.35f), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = null,
                        tint = accentColor,
                        modifier = Modifier.size(22.dp)
                    )
                }

                Spacer(modifier = Modifier.width(16.dp))

                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = title.uppercase(),
                        color = TextPrimary,
                        fontWeight = FontWeight.ExtraBold,
                        fontSize = 13.sp,
                        letterSpacing = 0.5.sp
                    )
                    Spacer(modifier = Modifier.height(3.dp))
                    Text(
                        text = tagline,
                        color = TextSecondary,
                        fontSize = 12.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                Spacer(modifier = Modifier.width(8.dp))

                // Count badge
                Box(
                    modifier = Modifier
                        .background(accentColor.copy(alpha = 0.15f), RoundedCornerShape(8.dp))
                        .border(0.5.dp, accentColor.copy(alpha = 0.3f), RoundedCornerShape(8.dp))
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = "$itemCount Items",
                        color = accentColor,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PortalScreen(
    portalKey: String,
    portalData: PortalData,
    onServiceClick: (ServiceItem) -> Unit,
    onIndustryClick: (IndustryItem) -> Unit
) {
    var searchQuery by remember { mutableStateOf("") }

    // Dynamic Category List
    val categories = remember(portalData.services) {
        val set = mutableSetOf("All")
        portalData.services.forEach {
            if (it.category.isNotEmpty()) set.add(it.category)
        }
        set.toList()
    }
    var selectedCategory by remember { mutableStateOf("All") }

    val filteredServices = remember(portalData.services, searchQuery, selectedCategory) {
        portalData.services.filter {
            (selectedCategory == "All" || it.category == selectedCategory) &&
            (it.title.contains(searchQuery, ignoreCase = true) ||
             it.description.contains(searchQuery, ignoreCase = true))
        }
    }

    val filteredIndustries = remember(portalData.industries, searchQuery) {
        portalData.industries.filter {
            it.title.contains(searchQuery, ignoreCase = true) ||
            it.description.contains(searchQuery, ignoreCase = true)
        }
    }

    val activeColor = when (portalKey) {
        "enterprise" -> NeonPurple
        "academy" -> NeonIndigo
        else -> NeonEmerald
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 16.dp)
    ) {
        // Search bar
        OutlinedTextField(
            value = searchQuery,
            onValueChange = { searchQuery = it },
            placeholder = { Text("Search details...", color = TextSecondary) },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = activeColor) },
            colors = OutlinedTextFieldDefaults.colors(
                focusedTextColor = TextPrimary,
                unfocusedTextColor = TextSecondary,
                focusedBorderColor = activeColor,
                unfocusedBorderColor = CardBorder,
                focusedContainerColor = CardDarkBg,
                unfocusedContainerColor = CardDarkBg
            ),
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 12.dp),
            shape = RoundedCornerShape(16.dp)
        )

        // Dynamic Category chips
        if (categories.size > 1) {
            LazyRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                contentPadding = PaddingValues(bottom = 12.dp)
            ) {
                items(categories) { category ->
                    val isSelected = category == selectedCategory
                    Box(
                        modifier = Modifier
                            .clip(RoundedCornerShape(20.dp))
                            .background(
                                if (isSelected) {
                                    Brush.horizontalGradient(
                                        colors = when (portalKey) {
                                            "enterprise" -> listOf(NeonPurple, NeonIndigo)
                                            "academy" -> listOf(NeonIndigo, NeonCyan)
                                            else -> listOf(NeonTeal, NeonEmerald)
                                        }
                                    )
                                } else {
                                    Brush.linearGradient(colors = listOf(CardDarkBg, CardDarkBg))
                                }
                            )
                            .border(
                                width = 1.dp,
                                color = if (isSelected) Color.Transparent else CardBorder,
                                shape = RoundedCornerShape(20.dp)
                            )
                            .clickable { selectedCategory = category }
                            .padding(horizontal = 14.dp, vertical = 8.dp)
                    ) {
                        Text(
                            text = category,
                            color = if (isSelected) ObsidianBg else TextSecondary,
                            fontWeight = FontWeight.Bold,
                            fontSize = 12.sp
                        )
                    }
                }
            }
        }

        LazyColumn(
            modifier = Modifier.weight(1f),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            contentPadding = PaddingValues(bottom = 80.dp) // Avoid floating bottom bar overlap
        ) {
            if (filteredServices.isNotEmpty()) {
                item {
                    Text(
                        text = if (portalKey == "academy") "PROGRAMS & COURSES" else "SERVICES OFFERED",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.ExtraBold,
                        color = TextPrimary,
                        letterSpacing = 1.sp,
                        modifier = Modifier.padding(vertical = 4.dp)
                    )
                }

                items(filteredServices) { service ->
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { onServiceClick(service) },
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = CardDarkBg),
                        border = BorderStroke(1.dp, CardBorder),
                        elevation = CardDefaults.cardElevation(2.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Box(
                                    modifier = Modifier
                                        .size(44.dp)
                                        .background(
                                            Brush.linearGradient(
                                                colors = when (portalKey) {
                                                    "enterprise" -> listOf(NeonPurple.copy(alpha = 0.2f), NeonIndigo.copy(alpha = 0.2f))
                                                    "academy" -> listOf(NeonIndigo.copy(alpha = 0.2f), NeonCyan.copy(alpha = 0.2f))
                                                    else -> listOf(NeonTeal.copy(alpha = 0.2f), NeonEmerald.copy(alpha = 0.2f))
                                                }
                                            ),
                                            RoundedCornerShape(10.dp)
                                        )
                                        .border(
                                            width = 1.dp,
                                            color = activeColor.copy(alpha = 0.4f),
                                            shape = RoundedCornerShape(10.dp)
                                        ),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        imageVector = getIconForName(service.icon),
                                        contentDescription = null,
                                        tint = activeColor,
                                        modifier = Modifier.size(22.dp)
                                    )
                                }
                                Spacer(modifier = Modifier.width(12.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(
                                        text = service.title,
                                        style = MaterialTheme.typography.bodyLarge,
                                        fontWeight = FontWeight.Bold,
                                        color = TextPrimary
                                    )
                                    if (service.category.isNotEmpty()) {
                                        Text(
                                            text = service.category.uppercase(),
                                            fontSize = 10.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = activeColor
                                        )
                                    }
                                }
                                Icon(
                                    imageVector = Icons.Default.KeyboardArrowRight,
                                    contentDescription = null,
                                    tint = TextSecondary
                                )
                            }
                            Spacer(modifier = Modifier.height(10.dp))
                            Text(
                                text = service.description,
                                style = MaterialTheme.typography.bodyMedium,
                                color = TextSecondary,
                                maxLines = 3,
                                overflow = TextOverflow.Ellipsis
                            )

                            // Tag list from CSV tech stack
                            if (service.tech_stack.isNotEmpty()) {
                                Spacer(modifier = Modifier.height(12.dp))
                                LazyRow(
                                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    items(service.tech_stack.split(",")) { tech ->
                                        Box(
                                            modifier = Modifier
                                                .background(
                                                    SurfaceLight.copy(alpha = 0.4f),
                                                    RoundedCornerShape(6.dp)
                                                )
                                                .border(0.5.dp, CardBorder, RoundedCornerShape(6.dp))
                                                .padding(horizontal = 8.dp, vertical = 4.dp)
                                        ) {
                                            Text(
                                                text = tech.trim(),
                                                fontSize = 10.sp,
                                                fontWeight = FontWeight.Bold,
                                                color = activeColor
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (filteredIndustries.isNotEmpty()) {
                item {
                    Text(
                        text = if (portalKey == "ai") "SECTOR SOLUTIONS" else "INDUSTRIES SUPPORTED",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.ExtraBold,
                        color = TextPrimary,
                        letterSpacing = 1.sp,
                        modifier = Modifier.padding(top = 16.dp, bottom = 4.dp)
                    )
                }

                items(filteredIndustries) { industry ->
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { onIndustryClick(industry) },
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = CardDarkBg),
                        border = BorderStroke(1.dp, CardBorder),
                        elevation = CardDefaults.cardElevation(2.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Box(
                                    modifier = Modifier
                                        .size(44.dp)
                                        .background(
                                            Brush.linearGradient(
                                                colors = when (portalKey) {
                                                    "enterprise" -> listOf(NeonPurple.copy(alpha = 0.2f), NeonIndigo.copy(alpha = 0.2f))
                                                    else -> listOf(NeonTeal.copy(alpha = 0.2f), NeonEmerald.copy(alpha = 0.2f))
                                                }
                                            ),
                                            RoundedCornerShape(10.dp)
                                        )
                                        .border(
                                            width = 1.dp,
                                            color = activeColor.copy(alpha = 0.4f),
                                            shape = RoundedCornerShape(10.dp)
                                        ),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        imageVector = getIconForName(industry.icon),
                                        contentDescription = null,
                                        tint = activeColor,
                                        modifier = Modifier.size(22.dp)
                                    )
                                }
                                Spacer(modifier = Modifier.width(12.dp))
                                Text(
                                    text = industry.title,
                                    style = MaterialTheme.typography.bodyLarge,
                                    fontWeight = FontWeight.Bold,
                                    color = TextPrimary,
                                    modifier = Modifier.weight(1f)
                                )
                                Icon(
                                    imageVector = Icons.Default.KeyboardArrowRight,
                                    contentDescription = null,
                                    tint = TextSecondary
                                )
                            }
                            Spacer(modifier = Modifier.height(10.dp))
                            Text(
                                text = industry.description,
                                style = MaterialTheme.typography.bodyMedium,
                                color = TextSecondary,
                                maxLines = 3,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// HTML Rich Text Composable
// ----------------------------------------------------
@Composable
fun HtmlText(
    html: String,
    modifier: Modifier = Modifier
) {
    AndroidView(
        modifier = modifier,
        factory = { ctx ->
            TextView(ctx).apply {
                setTextColor(android.graphics.Color.parseColor("#94A3B8")) // TextSecondary
                textSize = 14f
                movementMethod = LinkMovementMethod.getInstance()
                setLineSpacing(0f, 1.25f)
            }
        },
        update = { textView ->
            textView.text = HtmlCompat.fromHtml(html, HtmlCompat.FROM_HTML_MODE_COMPACT)
        }
    )
}

// ----------------------------------------------------
// Service Details Native Modal Dialog
// ----------------------------------------------------
@Composable
fun ServiceDetailDialog(
    service: ServiceItem,
    portalKey: String,
    onDismiss: () -> Unit,
    onGetEstimate: (String) -> Unit
) {
    val activeColor = when (portalKey) {
        "enterprise" -> NeonPurple
        "academy" -> NeonIndigo
        else -> NeonEmerald
    }

    AlertDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            Button(
                onClick = { onGetEstimate(service.title) },
                colors = ButtonDefaults.buttonColors(containerColor = activeColor),
                shape = RoundedCornerShape(10.dp)
            ) {
                Text("Get Estimate", color = ObsidianBg, fontWeight = FontWeight.Bold)
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Close", color = TextSecondary)
            }
        },
        title = {
            Text(
                text = service.title,
                fontWeight = FontWeight.ExtraBold,
                color = TextPrimary
            )
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Category Pill
                Box(
                    modifier = Modifier
                        .background(activeColor.copy(alpha = 0.12f), RoundedCornerShape(6.dp))
                        .border(1.dp, activeColor.copy(alpha = 0.4f), RoundedCornerShape(6.dp))
                        .padding(horizontal = 10.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = if (service.category.isNotEmpty()) service.category.uppercase() else "SERVICE OFFERING",
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        color = activeColor
                    )
                }

                // Description (Fallback / Summary)
                if (service.description.isNotEmpty()) {
                    Text(
                        text = service.description,
                        color = TextSecondary,
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        lineHeight = 20.sp
                    )
                }

                // Full Rich HTML Content
                if (service.content.isNotEmpty()) {
                    HtmlText(
                        html = service.content,
                        modifier = Modifier.fillMaxWidth()
                    )
                }

                // Tech stack tags
                if (service.tech_stack.isNotEmpty()) {
                    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Text(
                            text = "TECHNOLOGY MATRIX",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = TextPrimary,
                            letterSpacing = 1.sp
                        )
                        LazyRow(
                            horizontalArrangement = Arrangement.spacedBy(6.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            items(service.tech_stack.split(",")) { tech ->
                                Box(
                                    modifier = Modifier
                                        .background(SurfaceLight, RoundedCornerShape(6.dp))
                                        .border(0.5.dp, CardBorder, RoundedCornerShape(6.dp))
                                        .padding(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text(
                                        text = tech.trim(),
                                        fontSize = 10.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = activeColor
                                    )
                                }
                            }
                        }
                    }
                }

                // Dynamic Workflow (mirrors website)
                val steps = remember(service.category) {
                    when (service.category) {
                        "AI & Automation" -> listOf(
                            "Analyze business data & specify custom agent requirements.",
                            "Design NLP conversational trees or configure voice trunks.",
                            "Connect LLM pipelines (RAG vector models) to local data.",
                            "Perform tests on bot prompts and call workflows.",
                            "Deploy cloud instances and monitor feedback logs."
                        )
                        "SaaS Development" -> listOf(
                            "Architect secure multi-tenant structures and database bounds.",
                            "Establish API controllers (Node.js/Go) and dashboard structures.",
                            "Integrate subscription checkout and secure customer consoles.",
                            "Conduct load tests to guarantee seamless scale.",
                            "Launch containerized Docker environments."
                        )
                        "Marketing & Search" -> listOf(
                            "Run deep indexing crawls to identify search errors.",
                            "Generate semantic intent mapping profiles via AI keyword tool.",
                            "Perform metadata enhancements and optimize loading speed.",
                            "Optimize maps, review profile setups, and run paid ads.",
                            "Deliver diagnostic reports mapping traffic and keyword indexing."
                        )
                        "Web & Software" -> listOf(
                            "Collaborate on client specifications and CSS design tokens.",
                            "Construct robust backend servers and responsive frontend views.",
                            "Establish database tables and construct CMS portals.",
                            "Validate responsive mobile views and cross-browser formatting.",
                            "Deploy to secure hosts and configure SSL/domain security."
                        )
                        else -> listOf(
                            "Scope exact project requirements, goals, and technical dependencies.",
                            "Draft system diagrams, UI prototypes, and wireframe outlines.",
                            "Write secure, structured codebase utilizing modern coding frameworks.",
                            "Conduct thorough verification, unit testing, and layout audits.",
                            "Deploy production release and support operational launch."
                        )
                    }
                }

                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(
                        text = "IMPLEMENTATION WORKFLOW",
                        fontSize = 11.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = TextPrimary,
                        letterSpacing = 1.sp
                    )

                    steps.forEachIndexed { index, step ->
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.Top
                        ) {
                            Text(
                                text = "${index + 1}. ",
                                color = activeColor,
                                fontWeight = FontWeight.Bold,
                                fontSize = 13.sp
                            )
                            Text(
                                text = step,
                                color = TextSecondary,
                                fontSize = 13.sp,
                                lineHeight = 18.sp
                            )
                        }
                    }
                }
            }
        },
        containerColor = CardDarkBg,
        shape = RoundedCornerShape(16.dp),
        modifier = Modifier
            .fillMaxWidth()
            .padding(16.dp)
    )
}

// ----------------------------------------------------
// Industry Solution Native Modal Dialog
// ----------------------------------------------------
@Composable
fun IndustryDetailDialog(
    industry: IndustryItem,
    portalKey: String,
    onDismiss: () -> Unit,
    onGetEstimate: (String) -> Unit
) {
    val activeColor = when (portalKey) {
        "enterprise" -> NeonPurple
        else -> NeonEmerald
    }

    AlertDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            Button(
                onClick = { onGetEstimate(industry.title) },
                colors = ButtonDefaults.buttonColors(containerColor = activeColor),
                shape = RoundedCornerShape(10.dp)
            ) {
                Text("Get Estimate", color = ObsidianBg, fontWeight = FontWeight.Bold)
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Close", color = TextSecondary)
            }
        },
        title = {
            Text(
                text = industry.title,
                fontWeight = FontWeight.ExtraBold,
                color = TextPrimary
            )
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Box(
                    modifier = Modifier
                        .background(activeColor.copy(alpha = 0.12f), RoundedCornerShape(6.dp))
                        .border(1.dp, activeColor.copy(alpha = 0.4f), RoundedCornerShape(6.dp))
                        .padding(horizontal = 10.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = "INDUSTRY SECTOR SOLUTION",
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        color = activeColor
                    )
                }

                // Description (Fallback / Summary)
                if (industry.description.isNotEmpty()) {
                    Text(
                        text = industry.description,
                        color = TextSecondary,
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        lineHeight = 20.sp
                    )
                }

                // Full Rich HTML Content
                if (industry.content.isNotEmpty()) {
                    HtmlText(
                        html = industry.content,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            }
        },
        containerColor = CardDarkBg,
        shape = RoundedCornerShape(16.dp),
        modifier = Modifier
            .fillMaxWidth()
            .padding(16.dp)
    )
}

// ----------------------------------------------------
// Dynamic Project Cost Estimator & Multi-Step Lead Form
// ----------------------------------------------------
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LeadFormScreen(
    viewModel: MainScreenViewModel,
    data: ApiResponse,
    portal: String,
    onPortalChange: (String) -> Unit,
    serviceSelected: String,
    onServiceChange: (String) -> Unit,
    name: String,
    onNameChange: (String) -> Unit,
    email: String,
    onEmailChange: (String) -> Unit,
    phone: String,
    onPhoneChange: (String) -> Unit,
    message: String,
    onMessageChange: (String) -> Unit,
    estStep: Int,
    onStepChange: (Int) -> Unit,
    scaleSelected: String,
    onScaleChange: (String) -> Unit,
    timelineSelected: String,
    onTimelineChange: (String) -> Unit,
    currencySelected: String,
    onCurrencyChange: (String) -> Unit
) {
    val context = LocalContext.current
    val scrollState = rememberScrollState()
    var isSubmitting by remember { mutableStateOf(false) }

    val activeColor = when (portal) {
        "enterprise" -> NeonPurple
        "academy" -> NeonIndigo
        else -> NeonEmerald
    }

    val textFieldColors = OutlinedTextFieldDefaults.colors(
        focusedTextColor = TextPrimary,
        unfocusedTextColor = TextSecondary,
        focusedBorderColor = activeColor,
        unfocusedBorderColor = CardBorder,
        focusedLabelColor = activeColor,
        unfocusedLabelColor = TextSecondary,
        focusedContainerColor = CardDarkBg,
        unfocusedContainerColor = CardDarkBg
    )

    val coreServices = remember(portal, data) {
        when (portal) {
            "enterprise" -> data.enterprise.services
            "academy" -> data.academy.services
            else -> data.ai.services
        }
    }

    val industrySolutions = remember(portal, data) {
        when (portal) {
            "enterprise" -> data.enterprise.industries
            "academy" -> emptyList()
            else -> data.ai.industries
        }
    }


    // Helper function to resolve the slug for the selected title
    val selectedSlug = remember(portal, serviceSelected, data) {
        val selectedServiceItem = when (portal) {
            "enterprise" -> data.enterprise.services.find { it.title == serviceSelected }
            "academy" -> data.academy.services.find { it.title == serviceSelected }
            else -> data.ai.services.find { it.title == serviceSelected }
        }
        val selectedIndustryItem = if (selectedServiceItem == null && portal != "academy") {
            when (portal) {
                "enterprise" -> data.enterprise.industries.find { it.title == serviceSelected }
                else -> data.ai.industries.find { it.title == serviceSelected }
            }
        } else null
        selectedServiceItem?.slug ?: selectedIndustryItem?.slug ?: ""
    }

    fun calculateEstimate(
        slug: String,
        scale: String,
        timeline: String,
        currency: String,
        portal: String
    ): Pair<String, String> {
        val formatter = java.text.NumberFormat.getIntegerInstance(java.util.Locale.US)

        if (portal == "academy") {
            // Academy Course calculations
            val academyBaseFees = mapOf(
                "weboshop-fullstack-coding" to 15000,
                "hackion-cybersecurity" to 12000,
                "mobile-app-development" to 10000,
                "headless-cms-jamstack-coding" to 9500,
                "seo-digital-analytics-course" to 8000,
                "python-ai-engineering" to 11000,
                "seasonal-coding-internships" to 6000,
                "generative-ai-prompt-engineering" to 13000,
                "devops-cloud-orchestration" to 10500
            )
            // Backwards compatibility mappings for older database items
            val compatAcademy = mapOf(
                "weboshop" to 15000,
                "hackion" to 12000,
                "summer" to 8000,
                "winter" to 6000,
                "campus" to 5000,
                "school" to 3500
            )

            val base = academyBaseFees[slug] ?: compatAcademy[slug] ?: 5000
            val modeMult = when (scale) {
                "online" -> 0.85
                "offline" -> 1.0
                else -> 1.0
            }
            val batchMult = when (timeline) {
                "regular" -> 1.0
                "fast_track" -> 1.2
                else -> 1.0
            }
            val finalFee = base * modeMult * batchMult

            return when (currency) {
                "USD" -> {
                    val usdVal = Math.round((finalFee / 80.0) / 10.0) * 10.0
                    "$" + formatter.format(usdVal.toInt()) to "$" + formatter.format(usdVal.toInt())
                }
                "GBP" -> {
                    val gbpVal = Math.round((finalFee / 100.0) / 10.0) * 10.0
                    "£" + formatter.format(gbpVal.toInt()) to "£" + formatter.format(gbpVal.toInt())
                }
                "EUR" -> {
                    val eurVal = Math.round((finalFee / 90.0) / 10.0) * 10.0
                    "€" + formatter.format(eurVal.toInt()) to "€" + formatter.format(eurVal.toInt())
                }
                else -> {
                    val inrVal = Math.round(finalFee / 100.0) * 100.0
                    "₹" + formatter.format(inrVal.toInt()) to "₹" + formatter.format(inrVal.toInt())
                }
            }
        } else if (portal == "ai") {
            // AI Lab Portal Calculations
            val aiBaseCosts = mapOf(
                "ai-conversational-chatbots" to 90000,
                "outbound-ai-voice-agents" to 120000,
                "custom-saas-ai-platforms" to 150000,
                "ai-model-security-audits" to 80000,
                "headless-cms-jamstack-ai" to 70000,
                "whatsapp-ai-commerce" to 75000,
                "computer-vision-ocr" to 100000,
                "predictive-ai-forecasting" to 95000,
                "ai-education-erp" to 110000,
                "ai-healthcare-ehr" to 130000,
                "ai-pharmacy-ocr" to 85000,
                "ai-restaurant-pos" to 80000,
                "ai-hotel-booking" to 95000,
                "ai-salon-scheduling" to 75000,
                "ai-real-estate" to 100000,
                "ai-fintech" to 150000,
                "ai-logistics" to 120000,
                "ai-fitness" to 70000,
                "ai-travel" to 90000,
                "ai-legal" to 115000,
                "ai-hr" to 95000,
                "ai-car-rental" to 88000,
                "ai-events" to 82000,
                "ai-logistics-optimizer" to 125000
            )
            // Compatibility mappings
            val compatAi = mapOf(
                "ai_chatbots" to 90000,
                "ai_calling" to 120000,
                "ai_seo" to 35000,
                "saas_platform" to 150000,
                "llm_rag" to 100000,
                "cyber_audit" to 80000
            )

            val base = aiBaseCosts[slug] ?: compatAi[slug] ?: 80000
            val scaleMult = when (scale) {
                "startup" -> 1.0
                "business" -> 1.6
                "enterprise" -> 3.2
                else -> 1.0
            }
            val timeMult = when (timeline) {
                "fast" -> 1.3
                "standard" -> 1.0
                "extended" -> 0.85
                else -> 1.0
            }

            val calculatedBudget = base * scaleMult * timeMult
            val lowRange = calculatedBudget * 0.9
            val highRange = calculatedBudget * 1.1

            return when (currency) {
                "USD" -> {
                    val low = Math.round((lowRange / 80.0) / 100.0) * 100.0
                    val high = Math.round((highRange / 80.0) / 100.0) * 100.0
                    "$" + formatter.format(low.toInt()) to "$" + formatter.format(high.toInt())
                }
                "GBP" -> {
                    val low = Math.round((lowRange / 100.0) / 100.0) * 100.0
                    val high = Math.round((highRange / 100.0) / 100.0) * 100.0
                    "£" + formatter.format(low.toInt()) to "£" + formatter.format(high.toInt())
                }
                "EUR" -> {
                    val low = Math.round((lowRange / 90.0) / 100.0) * 100.0
                    val high = Math.round((highRange / 90.0) / 100.0) * 100.0
                    "€" + formatter.format(low.toInt()) to "€" + formatter.format(high.toInt())
                }
                else -> {
                    val low = Math.round(lowRange / 5000.0) * 5000.0
                    val high = Math.round(highRange / 5000.0) * 5000.0
                    "₹" + formatter.format(low.toInt()) to "₹" + formatter.format(high.toInt())
                }
            }
        } else {
            // Enterprise Portal Calculations (default)
            val enterpriseBaseCosts = mapOf(
                "web-designing-ui-ux" to 20000,
                "wordpress-cms-development" to 25000,
                "shopify-ecommerce-setups" to 40000,
                "laravel-php-web-apps" to 50000,
                "fullstack-mobile-development" to 80000,
                "seo-digital-marketing" to 15000,
                "headless-cms-jamstack" to 45000,
                "data-analytics-bi" to 35000,
                "cloud-server-setup" to 30000,
                "custom-saas-platforms" to 90000,
                "api-integrations" to 35000,
                "school-erp" to 75000,
                "hospital-management" to 95000,
                "pharmacy-billing" to 45000,
                "restaurant-pos" to 40000,
                "hotel-booking" to 55000,
                "salon-scheduling" to 35000,
                "real-estate" to 60000,
                "fintech-lending" to 110000,
                "logistics-tracking" to 80000,
                "gym-membership" to 30000,
                "travel-itineraries" to 50000,
                "legal-case-manager" to 55000,
                "hr-ats" to 65000,
                "car-rental" to 48000,
                "event-ticketing" to 42000,
                "agtech-yield" to 85000,
                "bespoke-crm-erp" to 120000
            )
            val compatEnterprise = mapOf(
                "web_design" to 20000,
                "wordpress" to 25000,
                "laravel" to 50000,
                "web_dev" to 60000,
                "mobile_apps" to 80000,
                "android_apps" to 75000,
                "magento" to 120000,
                "prestashop" to 45000,
                "moodle" to 55000,
                "custom_software" to 70000,
                "seo" to 15000,
                "marketing" to 20000,
                "cloud_servers" to 35000
            )

            val base = enterpriseBaseCosts[slug] ?: compatEnterprise[slug] ?: 30000
            val scaleMult = when (scale) {
                "startup" -> 1.0
                "business" -> 1.5
                "enterprise" -> 2.8
                else -> 1.0
            }
            val timeMult = when (timeline) {
                "fast" -> 1.3
                "standard" -> 1.0
                "extended" -> 0.85
                else -> 1.0
            }

            val calculatedBudget = base * scaleMult * timeMult
            val lowRange = calculatedBudget * 0.9
            val highRange = calculatedBudget * 1.1

            return when (currency) {
                "USD" -> {
                    val low = Math.round((lowRange / 80.0) / 100.0) * 100.0
                    val high = Math.round((highRange / 80.0) / 100.0) * 100.0
                    "$" + formatter.format(low.toInt()) to "$" + formatter.format(high.toInt())
                }
                "GBP" -> {
                    val low = Math.round((lowRange / 100.0) / 100.0) * 100.0
                    val high = Math.round((highRange / 100.0) / 100.0) * 100.0
                    "£" + formatter.format(low.toInt()) to "£" + formatter.format(high.toInt())
                }
                "EUR" -> {
                    val low = Math.round((lowRange / 90.0) / 100.0) * 100.0
                    val high = Math.round((highRange / 90.0) / 100.0) * 100.0
                    "€" + formatter.format(low.toInt()) to "€" + formatter.format(high.toInt())
                }
                else -> {
                    val low = Math.round(lowRange / 5000.0) * 5000.0
                    val high = Math.round(highRange / 5000.0) * 5000.0
                    "₹" + formatter.format(low.toInt()) to "₹" + formatter.format(high.toInt())
                }
            }
        }
    }

    val currentEstimate = remember(selectedSlug, scaleSelected, timelineSelected, currencySelected, portal) {
        calculateEstimate(selectedSlug, scaleSelected, timelineSelected, currencySelected, portal)
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(scrollState)
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // Form Title & Currency Switcher Header
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "PROJECT ESTIMATOR",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.ExtraBold,
                color = TextPrimary,
                letterSpacing = 1.sp
            )

            // Dynamic Currency Selector Buttons
            Row(
                horizontalArrangement = Arrangement.spacedBy(4.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                listOf("INR", "USD", "GBP", "EUR").forEach { curr ->
                    val isSelected = currencySelected == curr
                    val displaySymbol = when (curr) {
                        "USD" -> "$"
                        "GBP" -> "£"
                        "EUR" -> "€"
                        else -> "₹"
                    }
                    Box(
                        modifier = Modifier
                            .clip(RoundedCornerShape(6.dp))
                            .background(if (isSelected) activeColor else CardDarkBg)
                            .border(0.5.dp, if (isSelected) Color.Transparent else CardBorder, RoundedCornerShape(6.dp))
                            .clickable { onCurrencyChange(curr) }
                            .padding(horizontal = 8.dp, vertical = 4.dp)
                    ) {
                        Text(
                            text = displaySymbol,
                            color = if (isSelected) ObsidianBg else TextSecondary,
                            fontWeight = FontWeight.Bold,
                            fontSize = 11.sp
                        )
                    }
                }
            }
        }

        // Horizontal Step indicator
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            listOf("1. Service", "2. Scale", "3. Timeline", "4. Contact").forEachIndexed { index, label ->
                val stepNum = index + 1
                val isCurrent = estStep == stepNum
                val isPassed = estStep > stepNum
                Column(
                    modifier = Modifier.weight(1f),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(4.dp)
                            .background(
                                color = when {
                                    isCurrent -> activeColor
                                    isPassed -> activeColor.copy(alpha = 0.5f)
                                    else -> CardBorder
                                },
                                shape = RoundedCornerShape(2.dp)
                            )
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = label,
                        fontSize = 10.sp,
                        fontWeight = if (isCurrent) FontWeight.Bold else FontWeight.Medium,
                        color = if (isCurrent) TextPrimary else TextSecondary,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }
        }

        Card(
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = CardDarkBg),
            border = BorderStroke(1.dp, CardBorder),
            modifier = Modifier.fillMaxWidth()
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                when (estStep) {
                    // STEP 1: Select Portal & Service
                    1 -> {
                        Text(
                            text = "SELECT DIVISION & CAPABILITY",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = activeColor,
                            letterSpacing = 1.sp
                        )

                        // Division Selector Tab Row
                        TabRow(
                            selectedTabIndex = when (portal) {
                                "enterprise" -> 0
                                "academy" -> 1
                                else -> 2
                            },
                            containerColor = ObsidianBg,
                            contentColor = activeColor,
                            indicator = { tabPositions ->
                                TabRowDefaults.Indicator(
                                    modifier = Modifier.tabIndicatorOffset(tabPositions[when (portal) {
                                        "enterprise" -> 0
                                        "academy" -> 1
                                        else -> 2
                                    }]),
                                    color = activeColor
                                )
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(8.dp))
                        ) {
                            Tab(
                                selected = portal == "enterprise",
                                onClick = { onPortalChange("enterprise") }
                            ) {
                                Text(
                                    "Enterprise",
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Bold,
                                    modifier = Modifier.padding(vertical = 12.dp),
                                    color = if (portal == "enterprise") TextPrimary else TextSecondary
                                )
                            }
                            Tab(
                                selected = portal == "academy",
                                onClick = { onPortalChange("academy") }
                            ) {
                                Text(
                                    "Academy",
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Bold,
                                    modifier = Modifier.padding(vertical = 12.dp),
                                    color = if (portal == "academy") TextPrimary else TextSecondary
                                )
                            }
                            Tab(
                                selected = portal == "ai",
                                onClick = { onPortalChange("ai") }
                            ) {
                                Text(
                                    "AI Lab",
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Bold,
                                    modifier = Modifier.padding(vertical = 12.dp),
                                    color = if (portal == "ai") TextPrimary else TextSecondary
                                )
                            }
                        }

                        // List of available services & solutions as Option Cards
                        Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            if (coreServices.isNotEmpty()) {
                                Text(
                                    text = if (portal == "academy") "COURSES & PROGRAMS" else "CORE TECH SERVICES",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = activeColor,
                                    modifier = Modifier.padding(top = 8.dp, bottom = 4.dp)
                                )
                                coreServices.forEach { service ->
                                    val isSelected = serviceSelected == service.title
                                    val itemEst = calculateEstimate(
                                        service.slug,
                                        if (portal == "academy") "offline" else "startup",
                                        if (portal == "academy") "regular" else "standard",
                                        currencySelected,
                                        portal
                                    )

                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clip(RoundedCornerShape(12.dp))
                                            .background(if (isSelected) activeColor.copy(alpha = 0.08f) else ObsidianBg)
                                            .border(
                                                width = 1.dp,
                                                color = if (isSelected) activeColor else CardBorder,
                                                shape = RoundedCornerShape(12.dp)
                                            )
                                            .clickable { onServiceChange(service.title) }
                                            .padding(16.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Column(modifier = Modifier.weight(1f)) {
                                                Text(
                                                    text = service.title,
                                                    color = TextPrimary,
                                                    fontWeight = FontWeight.Bold,
                                                    fontSize = 14.sp
                                                )
                                                Spacer(modifier = Modifier.height(2.dp))
                                                Text(
                                                    text = if (portal == "academy") "Baseline tuition: ${itemEst.first}" else "Baseline: ${itemEst.first}",
                                                    color = TextSecondary,
                                                    fontSize = 11.sp
                                                )
                                            }
                                            RadioButton(
                                                selected = isSelected,
                                                onClick = { onServiceChange(service.title) },
                                                colors = RadioButtonDefaults.colors(selectedColor = activeColor)
                                            )
                                        }
                                    }
                                }
                            }

                            if (industrySolutions.isNotEmpty()) {
                                Spacer(modifier = Modifier.height(12.dp))
                                Text(
                                    text = if (portal == "ai") "AI INDUSTRY SOLUTIONS" else "INDUSTRY ERP SOLUTIONS",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = activeColor,
                                    modifier = Modifier.padding(top = 8.dp, bottom = 4.dp)
                                )
                                industrySolutions.forEach { industry ->
                                    val isSelected = serviceSelected == industry.title
                                    val itemEst = calculateEstimate(
                                        industry.slug,
                                        if (portal == "academy") "offline" else "startup",
                                        if (portal == "academy") "regular" else "standard",
                                        currencySelected,
                                        portal
                                    )

                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clip(RoundedCornerShape(12.dp))
                                            .background(if (isSelected) activeColor.copy(alpha = 0.08f) else ObsidianBg)
                                            .border(
                                                width = 1.dp,
                                                color = if (isSelected) activeColor else CardBorder,
                                                shape = RoundedCornerShape(12.dp)
                                            )
                                            .clickable { onServiceChange(industry.title) }
                                            .padding(16.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Column(modifier = Modifier.weight(1f)) {
                                                Text(
                                                    text = industry.title,
                                                    color = TextPrimary,
                                                    fontWeight = FontWeight.Bold,
                                                    fontSize = 14.sp
                                                )
                                                Spacer(modifier = Modifier.height(2.dp))
                                                Text(
                                                    text = "Baseline: ${itemEst.first}",
                                                    color = TextSecondary,
                                                    fontSize = 11.sp
                                                )
                                            }
                                            RadioButton(
                                                selected = isSelected,
                                                onClick = { onServiceChange(industry.title) },
                                                colors = RadioButtonDefaults.colors(selectedColor = activeColor)
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // STEP 2: Select Project Scale
                    2 -> {
                        Text(
                            text = if (portal == "academy") "CHOOSE LEARNING MODE" else "CHOOSE PROJECT SCALE",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = activeColor,
                            letterSpacing = 1.sp
                        )

                        val scales = if (portal == "academy") {
                            listOf(
                                Triple("online", "Online Mode (0.85x)", "Learn remotely from anywhere with live interactive sessions."),
                                Triple("offline", "Offline Classroom Mode (1.0x)", "Join our state-of-the-art labs for hands-on mentorship.")
                            )
                        } else {
                            listOf(
                                Triple("startup", "Startup / MVP (1.0x)", "Ideal for system validation, small launches, or pilot features."),
                                Triple("business", "Business Growth (1.6x)", "Standard production deployments with heightened data safety."),
                                Triple("enterprise", "Enterprise Scale (3.2x)", "Robust high-availability systems with SLA compliance.")
                            )
                        }

                        Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                            scales.forEach { (scaleKey, label, desc) ->
                                val isSelected = scaleSelected == scaleKey
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .clip(RoundedCornerShape(12.dp))
                                        .background(if (isSelected) activeColor.copy(alpha = 0.08f) else ObsidianBg)
                                        .border(
                                            width = 1.dp,
                                            color = if (isSelected) activeColor else CardBorder,
                                            shape = RoundedCornerShape(12.dp)
                                        )
                                        .clickable { onScaleChange(scaleKey) }
                                        .padding(16.dp)
                                ) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(
                                                text = label,
                                                color = TextPrimary,
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 14.sp
                                            )
                                            Spacer(modifier = Modifier.height(2.dp))
                                            Text(
                                                text = desc,
                                                color = TextSecondary,
                                                fontSize = 12.sp,
                                                lineHeight = 16.sp
                                            )
                                        }
                                        RadioButton(
                                            selected = isSelected,
                                            onClick = { onScaleChange(scaleKey) },
                                            colors = RadioButtonDefaults.colors(selectedColor = activeColor)
                                        )
                                    }
                                }
                            }
                        }
                    }

                    // STEP 3: Select Development Timeline
                    3 -> {
                        Text(
                            text = if (portal == "academy") "CHOOSE BATCH SCHEDULE" else "CHOOSE TARGET TIMELINE",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = activeColor,
                            letterSpacing = 1.sp
                        )

                        val timelines = if (portal == "academy") {
                            listOf(
                                Triple("regular", "Regular Batch (1.0x)", "Standard pace with comprehensive project reviews."),
                                Triple("fast_track", "Fast Track Batch (1.2x)", "Accelerated curriculum for rapid career transitioning.")
                            )
                        } else {
                            listOf(
                                Triple("extended", "Extended Priority (0.85x)", "Flexible timeline allowing maximum engineering balance."),
                                Triple("standard", "Standard Priority (1.0x)", "Balanced roadmap aligned with sprint deliveries."),
                                Triple("fast", "High Speed / Expedited (1.3x)", "Accelerated execution requiring dedicated resource allocation.")
                            )
                        }

                        Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                            timelines.forEach { (timeKey, label, desc) ->
                                val isSelected = timelineSelected == timeKey
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .clip(RoundedCornerShape(12.dp))
                                        .background(if (isSelected) activeColor.copy(alpha = 0.08f) else ObsidianBg)
                                        .border(
                                            width = 1.dp,
                                            color = if (isSelected) activeColor else CardBorder,
                                            shape = RoundedCornerShape(12.dp)
                                        )
                                        .clickable { onTimelineChange(timeKey) }
                                        .padding(16.dp)
                                ) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(
                                                text = label,
                                                color = TextPrimary,
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 14.sp
                                            )
                                            Spacer(modifier = Modifier.height(2.dp))
                                            Text(
                                                text = desc,
                                                color = TextSecondary,
                                                fontSize = 12.sp,
                                                lineHeight = 16.sp
                                            )
                                        }
                                        RadioButton(
                                            selected = isSelected,
                                            onClick = { onTimelineChange(timeKey) },
                                            colors = RadioButtonDefaults.colors(selectedColor = activeColor)
                                        )
                                    }
                                }
                            }
                        }
                    }

                    // STEP 4: Review Estimate & Submit Lead
                    4 -> {
                        Text(
                            text = "ESTIMATE QUOTE RESULT",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = activeColor,
                            letterSpacing = 1.sp
                        )

                        // Budget Display Box
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .background(ObsidianBg, RoundedCornerShape(12.dp))
                                .border(1.dp, activeColor.copy(alpha = 0.5f), RoundedCornerShape(12.dp))
                                .padding(20.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                Text(
                                    text = if (portal == "academy") "ESTIMATED TUITION FEE" else "ESTIMATED PRICE RANGE",
                                    color = TextSecondary,
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.Bold,
                                    letterSpacing = 1.sp
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = if (currentEstimate.first == currentEstimate.second) currentEstimate.first else "${currentEstimate.first} - ${currentEstimate.second}",
                                    color = activeColor,
                                    fontWeight = FontWeight.ExtraBold,
                                    fontSize = 24.sp
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                val scaleLabel = when (scaleSelected) {
                                    "online" -> "Online"
                                    "offline" -> "Offline"
                                    "startup" -> "Startup"
                                    "business" -> "Business"
                                    "enterprise" -> "Enterprise"
                                    else -> scaleSelected.replaceFirstChar { if (it.isLowerCase()) it.titlecase(java.util.Locale.getDefault()) else it.toString() }
                                }
                                val timelineLabel = when (timelineSelected) {
                                    "regular" -> "Regular"
                                    "fast_track" -> "Fast Track"
                                    "extended" -> "Extended"
                                    "standard" -> "Standard"
                                    "fast" -> "Fast"
                                    else -> timelineSelected.replaceFirstChar { if (it.isLowerCase()) it.titlecase(java.util.Locale.getDefault()) else it.toString() }
                                }
                                Text(
                                    text = if (portal == "academy") "*Based on $scaleLabel mode & $timelineLabel batch schedule." else "*Based on $scaleLabel scale & $timelineLabel delivery timeline.",
                                    color = TextMuted,
                                    fontSize = 10.sp
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(8.dp))

                        // Contact Fields
                        OutlinedTextField(
                            value = name,
                            onValueChange = onNameChange,
                            label = { Text("Your Name *") },
                            colors = textFieldColors,
                            modifier = Modifier.fillMaxWidth()
                        )

                        OutlinedTextField(
                            value = email,
                            onValueChange = onEmailChange,
                            label = { Text("Your Email *") },
                            colors = textFieldColors,
                            modifier = Modifier.fillMaxWidth()
                        )

                        OutlinedTextField(
                            value = phone,
                            onValueChange = onPhoneChange,
                            label = { Text("Your Phone Number") },
                            colors = textFieldColors,
                            modifier = Modifier.fillMaxWidth()
                        )

                        OutlinedTextField(
                            value = message,
                            onValueChange = onMessageChange,
                            label = { Text("Project Details / Extra Details") },
                            colors = textFieldColors,
                            modifier = Modifier.fillMaxWidth(),
                            minLines = 3
                        )
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))

                // Bottom Action buttons (Back/Next)
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    if (estStep > 1) {
                        OutlinedButton(
                            onClick = { onStepChange(estStep - 1) },
                            colors = ButtonDefaults.outlinedButtonColors(contentColor = TextPrimary),
                            border = BorderStroke(1.dp, CardBorder),
                            shape = RoundedCornerShape(10.dp),
                            modifier = Modifier
                                .weight(1f)
                                .height(48.dp)
                        ) {
                            Text("Back", fontWeight = FontWeight.Bold)
                        }
                    }

                    Button(
                        onClick = {
                            if (estStep < 4) {
                                onStepChange(estStep + 1)
                            } else {
                                // Validate Step 4 inputs
                                if (name.isBlank() || email.isBlank()) {
                                    Toast.makeText(context, "Name and Email are required.", Toast.LENGTH_SHORT).show()
                                    return@Button
                                }

                                isSubmitting = true
                                viewModel.submitLead(
                                    portal = portal,
                                    name = name,
                                    email = email,
                                    phone = phone,
                                    service = serviceSelected,
                                    duration = if (portal == "academy") {
                                        val modeStr = scaleSelected.replaceFirstChar { if (it.isLowerCase()) it.titlecase(java.util.Locale.getDefault()) else it.toString() }
                                        val batchStr = if (timelineSelected == "fast_track") "Fast Track" else timelineSelected.replaceFirstChar { if (it.isLowerCase()) it.titlecase(java.util.Locale.getDefault()) else it.toString() }
                                        "Mode: $modeStr | Batch: $batchStr"
                                    } else {
                                        val scaleStr = scaleSelected.replaceFirstChar { if (it.isLowerCase()) it.titlecase(java.util.Locale.getDefault()) else it.toString() }
                                        val timelineStr = timelineSelected.replaceFirstChar { if (it.isLowerCase()) it.titlecase(java.util.Locale.getDefault()) else it.toString() }
                                        "Scale: $scaleStr | Timeline: $timelineStr"
                                    },
                                    budget = if (currentEstimate.first == currentEstimate.second) currentEstimate.first else "${currentEstimate.first} - ${currentEstimate.second}",
                                    message = if (portal == "academy") "Batch: $timelineSelected. Details: $message" else "Timeline: $timelineSelected. Details: $message",
                                    leadType = "Estimator"
                                ) { success ->
                                    isSubmitting = false
                                    if (success) {
                                        Toast.makeText(context, "Proposal Request Submitted!", Toast.LENGTH_LONG).show()
                                        // Reset fields
                                        onNameChange("")
                                        onEmailChange("")
                                        onPhoneChange("")
                                        onMessageChange("")
                                        onStepChange(1) // Return to Step 1
                                    } else {
                                        Toast.makeText(context, "Submission failed. Verify server is active.", Toast.LENGTH_LONG).show()
                                    }
                                }
                            }
                        },
                        modifier = Modifier
                            .weight(1f)
                            .height(48.dp)
                            .background(
                                Brush.horizontalGradient(
                                    colors = listOf(NeonIndigo, NeonCyan)
                                ),
                                shape = RoundedCornerShape(10.dp)
                            ),
                        colors = ButtonDefaults.buttonColors(containerColor = Color.Transparent),
                        enabled = !isSubmitting,
                        shape = RoundedCornerShape(10.dp),
                        contentPadding = PaddingValues(0.dp)
                    ) {
                        if (isSubmitting) {
                            CircularProgressIndicator(color = ObsidianBg, modifier = Modifier.size(24.dp))
                        } else {
                            Text(
                                text = if (estStep == 4) "Submit Lead" else "Continue",
                                fontWeight = FontWeight.Bold,
                                color = ObsidianBg
                            )
                        }
                    }
                }
            }
        }
        Spacer(modifier = Modifier.height(80.dp)) // Avoid floating bottom bar overlap
    }
}

fun getIconForName(iconName: String): androidx.compose.ui.graphics.vector.ImageVector {
    return when (iconName.lowercase()) {
        "globe" -> Icons.Default.Home
        "cpu", "processor", "smart_toy", "android" -> Icons.Default.Star
        "heart" -> Icons.Default.Favorite
        "briefcase", "work", "business" -> Icons.Default.Build
        "compass" -> Icons.Default.LocationOn
        "users", "group", "people" -> Icons.Default.AccountCircle
        "car" -> Icons.Default.Build
        "ticket" -> Icons.Default.Send
        "truck" -> Icons.Default.ArrowForward
        else -> Icons.Default.Star
    }
}
